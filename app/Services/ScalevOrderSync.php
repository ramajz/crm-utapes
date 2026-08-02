<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Handler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ScalevOrderSync
{
    public int $handlersCreated = 0;
    public int $customersCreated = 0;
    public int $ordersInserted = 0;
    public int $ordersUpdated = 0;
    public int $itemsInserted = 0;

    private array $handlerMap = [];
    private array $customerMap = [];

    public function __construct()
    {
        $this->loadMaps();
    }

    /**
     * Upsert order (+ items, customer, handler) dari satu event webhook Scalev.
     */
    public function processEvent(string $event, array $data): array
    {
        if (!in_array($event, ['order.created', 'order.payment_status_changed'], true)) {
            return ['action' => 'skipped'];
        }

        if (!isset($data['order_id'])) {
            return ['action' => 'skipped'];
        }

        return DB::transaction(function () use ($event, $data) {
            $orderId = (string) $data['order_id'];

            $fields = $event === 'order.created'
                ? $this->fieldsFromCreated($data)
                : $this->fieldsFromPaymentChanged($data);

            if (empty($fields)) {
                return ['action' => 'skipped'];
            }

            if (isset($data['customer']['phone'])) {
                $phone = $this->normalizePhone($data['customer']['phone']);
                if ($phone !== null) {
                    $name = trim((string) ($data['customer']['name'] ?? '')) ?: ('Customer ' . substr($phone, -4));
                    $fields['customer_id'] = $this->resolveCustomer($phone, $name);
                }
            }

            $fields['order_id'] = $orderId;
            $fields['updated_at'] = now();

            $before = DB::table('orders')->where('order_id', $orderId)->exists();

            // paid_time dianggap monoton: pertahankan nilai terbesar lintas event
            if (isset($fields['paid_time']) && $before) {
                $existingPt = DB::table('orders')->where('order_id', $orderId)->value('paid_time');
                if ($existingPt && Carbon::parse($existingPt)->gt($fields['paid_time'])) {
                    unset($fields['paid_time']);
                }
            }

            if (!$before) {
                $fields['created_at'] = now();
            }

            DB::table('orders')->upsert($fields, ['order_id'], array_keys($fields));

            if ($before) {
                $this->ordersUpdated++;
            } else {
                $this->ordersInserted++;
            }

            // Item hanya diambil dari event order.created sekali per order.
            if ($event === 'order.created' && !$before && isset($data['orderlines'])) {
                $orderRow = DB::table('orders')->where('order_id', $orderId)->first(['id']);
                if ($orderRow) {
                    $this->insertItems($orderRow->id, $data['orderlines']);
                }
            }

            return ['action' => $before ? 'updated' : 'inserted', 'order_id' => $orderId];
        });
    }

    private function fieldsFromCreated(array $d): array
    {
        $mv = is_array($d['message_variables'] ?? null) ? $d['message_variables'] : [];
        $metadata = is_array($d['metadata'] ?? null) ? $d['metadata'] : [];
        $sourceUrl = $metadata['event_source_url'] ?? $mv['event_source_url'] ?? null;
        $utm = $this->extractUtm($sourceUrl);

        $handlerName = trim((string) ($mv['handler'] ?? $d['handler'] ?? ''));
        $fields = [
            'status' => $d['status'] ?? 'draft',
            'payment_status' => $d['payment_status'] ?? 'unpaid',
            'is_probably_spam' => (bool) ($d['is_probably_spam'] ?? false),
            'source' => 'scalev',
            'created_time' => $this->parseDt($d['created_at'] ?? $d['draft_time'] ?? null),
            'draft_time' => $this->parseDt($d['draft_time'] ?? null),
            'pending_time' => $this->parseDt($d['pending_time'] ?? null),
            'confirmed_time' => $this->parseDt($d['confirmed_time'] ?? null),
            'in_process_time' => $this->parseDt($d['in_process_time'] ?? null),
            'ready_time' => $this->parseDt($d['ready_time'] ?? null),
            'shipped_time' => $this->parseDt($d['shipped_time'] ?? null),
            'completed_time' => $this->parseDt($d['completed_time'] ?? null),
            'rts_time' => $this->parseDt($d['rts_time'] ?? null),
            'canceled_time' => $this->parseDt($d['canceled_time'] ?? null),
            'closed_time' => $this->parseDt($d['closed_time'] ?? null),
            'unpaid_time' => $this->parseDt($d['unpaid_time'] ?? null),
            'paid_time' => $this->parseDt($d['paid_time'] ?? null),
            'conflict_time' => $this->parseDt($d['conflict_time'] ?? null),
            'settled_time' => $this->parseDt($d['settled_time'] ?? null),
            'transfer_time' => $this->parseDt($d['transfer_time'] ?? null),
            'payment_method' => $d['payment_method'] ?? null,
            'epayment_provider' => $d['epayment_provider'] ?? null,
            'financial_entity' => $this->entityName($d['financial_entity'] ?? null),
            'payment_account_holder' => $d['payment_account_holder'] ?? null,
            'payment_account_number' => $d['payment_account_number'] ?? null,
            'transferproof_url' => $d['transferproof_url'] ?? null,
            'gross_revenue' => $this->money($d['gross_revenue'] ?? 0),
            'scalev_fee' => $this->money($d['scalev_fee'] ?? 0),
            'payment_fee' => $this->money($d['payment_fee'] ?? 0),
            'net_payment_revenue' => $this->money($d['net_payment_revenue'] ?? 0),
            'unique_code_discount' => $this->money($d['unique_code_discount'] ?? 0),
            'discount_code_discount' => $this->money($d['discount_code_discount'] ?? 0),
            'net_revenue' => $this->money($d['net_revenue'] ?? 0),
            'product_price' => $this->money($d['product_price'] ?? 0),
            'product_discount' => $this->money($d['product_discount'] ?? 0),
            'other_income' => $this->money($d['other_income'] ?? 0),
            'cogs' => $this->money($d['cogs'] ?? 0),
            'shipping_cost' => $this->money($d['shipping_cost'] ?? 0),
            'shipping_discount' => $this->money($d['shipping_discount'] ?? 0),
            'discount_rate' => round((float) ($d['discount_rate'] ?? 0), 2),
            'total_quantity' => (int) ($d['total_quantity'] ?? 0),
            'total_weight' => (int) ($d['total_weight'] ?? 0),
            'advertiser' => $mv['advertiser'] ?? null,
            'utm_source' => $utm['source'],
            'utm_medium' => $utm['medium'],
            'utm_campaign' => $utm['campaign'],
            'utm_content' => $utm['content'],
            'utm_id' => $utm['id'],
            'traffic_type' => (new UtmParserService)->classify($utm['source'], $utm['medium']),
            'store' => is_array($d['store'] ?? null) ? ($d['store']['name'] ?? null) : null,
            'destination' => isset($d['destination_address']) ? json_encode($d['destination_address'], JSON_INVALID_UTF8_SUBSTITUTE) : null,
            'notes' => $d['notes'] ?? null,
            'raw_payload' => $this->toJson($this->compactPayload($d)),
        ];

        if ($handlerName !== '') {
            $fields['handler_id'] = $this->resolveHandler($handlerName);
        }

        return $this->filterNulls($fields);
    }

    private function fieldsFromPaymentChanged(array $d): array
    {
        $fields = [
            'payment_status' => $d['payment_status'] ?? null,
            'is_probably_spam' => isset($d['is_probably_spam']) ? (bool) $d['is_probably_spam'] : null,
            'unpaid_time' => $this->parseDt($d['unpaid_time'] ?? null),
            'paid_time' => $this->parseDt($d['paid_time'] ?? null),
            'conflict_time' => $this->parseDt($d['conflict_time'] ?? null),
            'settled_time' => $this->parseDt($d['settled_time'] ?? null),
            'transfer_time' => $this->parseDt($d['transfer_time'] ?? null),
            'payment_method' => $d['payment_method'] ?? null,
            'epayment_provider' => $d['epayment_provider'] ?? null,
            'financial_entity' => $this->entityName($d['financial_entity'] ?? null),
            'payment_account_holder' => $d['payment_account_holder'] ?? null,
            'payment_account_number' => $d['payment_account_number'] ?? null,
            'transferproof_url' => $d['transferproof_url'] ?? null,
            'pg_reference_id' => $d['pg_reference_id'] ?? null,
            'pg_payment_info' => $this->toJson($d['pg_payment_info'] ?? null),
        ];

        if (isset($d['customer']['phone'])) {
            $phone = $this->normalizePhone($d['customer']['phone']);
            if ($phone !== null) {
                $name = trim((string) ($d['customer']['name'] ?? '')) ?: ('Customer ' . substr($phone, -4));
                $fields['customer_id'] = $this->resolveCustomer($phone, $name);
            }
        }

        return $this->filterNulls($fields);
    }

    private function insertItems(int $orderPk, array $orderlines): void
    {
        $rows = [];
        foreach ($orderlines as $i => $line) {
            if (!is_array($line)) {
                continue;
            }
            $rows[] = [
                'order_id' => $orderPk,
                'position' => $i,
                'product_name' => $line['product_name'] ?? null,
                'variant_unique_id' => $line['variant_unique_id'] ?? null,
                'variant_sku' => $line['variant_sku'] ?? null,
                'quantity' => (int) ($line['quantity'] ?? 1),
                'weight' => (int) ($line['weight'] ?? 0),
                'product_price' => $this->money($line['product_price'] ?? 0),
                'variant_price' => $this->money($line['variant_price'] ?? 0),
                'variant_cogs' => $this->money($line['variant_cogs'] ?? 0),
                'discount' => $this->money($line['discount'] ?? 0),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($rows) {
            DB::table('order_items')->insert($rows);
            $this->itemsInserted += count($rows);
        }
    }

    private function extractUtm(?string $url): array
    {
        $utm = ['source' => null, 'medium' => null, 'campaign' => null, 'content' => null, 'id' => null];
        if (!$url) {
            return $utm;
        }
        $query = parse_url($url, PHP_URL_QUERY);
        if (!is_string($query) || $query === '') {
            return $utm;
        }
        parse_str($query, $params);
        $utm['source'] = $params['utm_source'] ?? null;
        $utm['medium'] = $params['utm_medium'] ?? null;
        $utm['campaign'] = $params['utm_campaign'] ?? null;
        $utm['content'] = $params['utm_content'] ?? null;
        $utm['id'] = $params['utm_id'] ?? null;
        return $utm;
    }

    private function compactPayload(array $d): array
    {
        if (isset($d['customer'])) {
            $d['customer'] = [
                'id' => $d['customer']['id'] ?? null,
                'name' => $d['customer']['name'] ?? null,
                'phone' => $d['customer']['phone'] ?? null,
                'created_at' => $d['customer']['created_at'] ?? null,
            ];
        }
        unset($d['message_variables'], $d['secret_slug'], $d['business'], $d['origin_address']);
        return $d;
    }

    private function filterNulls(array $fields): array
    {
        return array_filter($fields, fn ($v) => $v !== null);
    }

    private function entityName($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_array($value)) {
            return $value['name'] ?? json_encode($value);
        }
        return (string) $value;
    }

    private function toJson($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $json = json_encode($value, JSON_INVALID_UTF8_SUBSTITUTE);
        return $json === false ? null : $json;
    }

    private function money($value): int
    {
        return (int) round((float) str_replace(',', '', (string) $value));
    }

    private function parseDt($value): ?Carbon
    {
        if (!$value || $value === '') {
            return null;
        }
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function loadMaps(): void
    {
        $this->handlerMap = Handler::query()->pluck('id', 'name')->all();
        $this->customerMap = Customer::query()->whereNotNull('phone')->pluck('id', 'phone')->all();
    }

    private function resolveHandler(string $name): ?int
    {
        if (isset($this->handlerMap[$name])) {
            return $this->handlerMap[$name];
        }
        $handler = Handler::create(['name' => $name, 'is_active' => true]);
        $this->handlerMap[$name] = $handler->id;
        $this->handlersCreated++;
        return $handler->id;
    }

    private function resolveCustomer(string $phone, string $name): ?int
    {
        if (isset($this->customerMap[$phone])) {
            return $this->customerMap[$phone];
        }
        $customer = Customer::create(['phone' => $phone, 'name' => $name]);
        $this->customerMap[$phone] = $customer->id;
        $this->customersCreated++;
        return $customer->id;
    }

    private function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62' . $digits;
        }
        return $digits;
    }
}
