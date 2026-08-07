<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadHistory;
use App\Models\Handler;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LeadService
{
    const FINANCIAL_STATUS_CLOSING = 'paid';

    public function __construct(
        protected UtmParserService $utmParser,
        protected LoyaltyService $loyaltyService,
        protected LeadAssignmentService $leadAssignment,
    ) {}

    /**
     * Create a new lead from webhook or CSV import.
     */
    public function createFromData(array $data): Lead
    {
        $validated = Validator::make($data, [
            'phone' => 'required|string|max:20',
            'customer_name' => 'required|string|max:255',
            'order_id' => 'required|string|max:50',
            'total_value' => 'nullable|numeric|min:0',
            'handler_id' => 'nullable|exists:handlers,id',
            'financial_status' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
            'size' => 'nullable|string|max:5',
            'utm_source' => 'nullable|string|max:100',
            'utm_medium' => 'nullable|string|max:100',
            'utm_campaign' => 'nullable|string|max:100',
            'utm_content' => 'nullable|string|max:100',
            'timestamp' => 'nullable|date',
        ])->validate();

        $create = function () use ($validated) {
            return DB::transaction(function () use ($validated) {
                // Find or create customer
                $customer = $this->loyaltyService->findOrCreate(
                    $validated['phone'],
                    $validated['customer_name'],
                    $validated['total_value'] ?? 0
                );

                // Parse traffic type from UTM
                $trafficType = $this->utmParser->classify(
                    $validated['utm_source'] ?? null,
                    $validated['utm_medium'] ?? null
                );

                // Create lead
                $lead = Lead::create([
                    'order_id' => $validated['order_id'],
                    'customer_id' => $customer->id,
                    'handler_id' => $this->leadAssignment->assignWithoutLock($validated['handler_id'] ?? null),
                    'financial_status' => $validated['financial_status'] ?? 'unpaid',
                    'total_value' => $validated['total_value'] ?? 0,
                    'funnel_stage' => 'cold',
                    'status_fu' => 'new',
                    'notes' => $validated['notes'] ?? null,
                    'size' => $validated['size'] ?? null,
                    'utm_source' => $validated['utm_source'] ?? null,
                    'utm_medium' => $validated['utm_medium'] ?? null,
                    'utm_campaign' => $validated['utm_campaign'] ?? null,
                    'utm_content' => $validated['utm_content'] ?? null,
                    'traffic_type' => $trafficType,
                    'lead_type' => $customer->total_orders > 1 ? 'repeat' : 'new',
                    'timestamp' => $validated['timestamp'] ?? now(),
                    'last_update_at' => $validated['timestamp'] ?? now(),
                ]);

                return $lead;
            });
        };

        return array_key_exists('handler_id', $validated) && $validated['handler_id'] !== null
            ? $create()
            : $this->leadAssignment->withAssignmentLock($create);
    }

    /**
     * Update lead status with business logic:
     * - Auto-set funnel_stage based on status_fu
     * - Auto-set payment to paid if status_fu is "closing"
     * - Track first_replied_at on first status change from "new"
     * - Create audit trail entry
     */
    public function updateStatus(Lead $lead, array $data, int $userId): Lead
    {
        return DB::transaction(function () use ($lead, $data, $userId) {
            $oldStatusFu = $lead->status_fu;
            $newStatusFu = $data['status_fu'] ?? $oldStatusFu;

            // Auto-funnel: map status_fu → funnel_stage
            $newFunnel = Lead::mapStatusToFunnel($newStatusFu);

            // Auto-payment: if closing → paid
            $newFinancialStatus = $lead->financial_status;
            if (Lead::isClosingStatus($newStatusFu)) {
                $newFinancialStatus = self::FINANCIAL_STATUS_CLOSING;
            }

            // First response time tracking
            $firstRepliedAt = $lead->first_replied_at;
            if ($oldStatusFu === 'new' && $newStatusFu !== 'new' && !$firstRepliedAt) {
                $firstRepliedAt = now();
            }

            // Track changes for audit trail
            $changes = [];
            if ($oldStatusFu !== $newStatusFu) {
                $changes[] = [
                    'lead_id' => $lead->id,
                    'user_id' => $userId,
                    'field_changed' => 'status_fu',
                    'old_value' => $oldStatusFu,
                    'new_value' => $newStatusFu,
                ];
            }
            if ($lead->funnel_stage !== $newFunnel) {
                $changes[] = [
                    'lead_id' => $lead->id,
                    'user_id' => $userId,
                    'field_changed' => 'funnel_stage',
                    'old_value' => $lead->funnel_stage,
                    'new_value' => $newFunnel,
                ];
            }
            if (array_key_exists('notes', $data) && $lead->notes !== $data['notes']) {
                $changes[] = [
                    'lead_id' => $lead->id,
                    'user_id' => $userId,
                    'field_changed' => 'notes',
                    'old_value' => $lead->notes,
                    'new_value' => $data['notes'],
                ];
            }
            if (array_key_exists('size', $data) && $lead->size !== $data['size']) {
                $changes[] = [
                    'lead_id' => $lead->id,
                    'user_id' => $userId,
                    'field_changed' => 'size',
                    'old_value' => $lead->size,
                    'new_value' => $data['size'],
                ];
            }

            // Save history entries
            if (!empty($changes)) {
                $now = now();
                foreach ($changes as &$change) {
                    $change['created_at'] = $now;
                }
                unset($change);
                LeadHistory::insert($changes);
            }

            // Update lead
            $lead->update([
                'status_fu' => $newStatusFu,
                'funnel_stage' => $newFunnel,
                'financial_status' => $newFinancialStatus,
                'notes' => $data['notes'] ?? $lead->notes,
                'size' => $data['size'] ?? $lead->size,
                'first_replied_at' => $firstRepliedAt ?? $lead->first_replied_at,
                'last_update_at' => now(),
            ]);

            return $lead->fresh();
        });
    }

    /**
     * Tandai / hapus penanda lead wajib follow-up oleh manager/admin.
     * Saat ditandai → follow_up_status = pending; saat dihapus → status & completed direset.
     */
    public function markFollowUp(Lead $lead, bool $required, int $userId): Lead
    {
        return DB::transaction(function () use ($lead, $required, $userId) {
            $oldValue = $lead->follow_up_required ? '1' : '0';
            $newValue = $required ? '1' : '0';

            $lead->update([
                'follow_up_required' => $required,
                'follow_up_status' => $required ? 'pending' : null,
                'follow_up_completed_at' => $required ? $lead->follow_up_completed_at : null,
            ]);

            if ($oldValue !== $newValue) {
                LeadHistory::create([
                    'lead_id' => $lead->id,
                    'user_id' => $userId,
                    'field_changed' => 'follow_up_required',
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                ]);
            }

            return $lead->fresh();
        });
    }

    /**
     * CS menandai lead wajib follow-up selesai dikerjakan.
     */
    public function completeFollowUp(Lead $lead, int $userId): Lead
    {
        if (!$lead->follow_up_required || $lead->follow_up_status === 'done') {
            return $lead;
        }

        return DB::transaction(function () use ($lead, $userId) {
            LeadHistory::create([
                'lead_id' => $lead->id,
                'user_id' => $userId,
                'field_changed' => 'follow_up_status',
                'old_value' => $lead->follow_up_status,
                'new_value' => 'done',
            ]);

            $lead->update([
                'follow_up_status' => 'done',
                'follow_up_completed_at' => now(),
                'last_update_at' => now(),
            ]);

            return $lead->fresh();
        });
    }

    /**
     * Pindahkan lead ke handler/CS lain. Status wajib follow-up ikut terbawa.
     */
    public function reassignHandler(Lead $lead, int $handlerId, int $userId): Lead
    {
        return DB::transaction(function () use ($lead, $handlerId, $userId) {
            $handler = Handler::findOrFail($handlerId);
            $oldHandler = $lead->handler;

            if ($lead->handler_id === $handlerId) {
                return $lead;
            }

            LeadHistory::create([
                'lead_id' => $lead->id,
                'user_id' => $userId,
                'field_changed' => 'handler_id',
                'old_value' => $oldHandler?->name,
                'new_value' => $handler->name,
            ]);

            $lead->update([
                'handler_id' => $handlerId,
                'last_update_at' => now(),
            ]);

            return $lead->fresh();
        });
    }

    /**
     * Pindahkan banyak lead ke satu handler sekaligus. Return jumlah yang dipindah.
     */
    public function bulkReassign(array $leadIds, int $handlerId, int $userId): int
    {
        $leadIds = array_map('intval', $leadIds);

        return DB::transaction(function () use ($leadIds, $handlerId, $userId) {
            $count = 0;
            foreach ($leadIds as $leadId) {
                $lead = Lead::find($leadId);
                if (!$lead || $lead->handler_id === $handlerId) {
                    continue;
                }
                $this->reassignHandler($lead, $handlerId, $userId);
                $count++;
            }
            return $count;
        });
    }

    /**
     * Get dashboard stats for a specific handler (CS).
     */
    public function getHandlerStats(int $handlerId, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = Lead::byHandler($handlerId);

        if ($startDate && $endDate) {
            $query->byDateRange($startDate, $endDate);
        }

        $total = (clone $query)->count();
        $followedUp = (clone $query)->followedUp()->count();
        $notFollowedUp = (clone $query)->notFollowedUp()->count();

        // Closing & revenue berdasarkan kapan order dibayar (orders.paid_time, sumber Scalev)
        $closingQuery = Order::where('handler_id', $handlerId);
        if ($startDate && $endDate) {
            $closingQuery->whereBetween('paid_time', [$startDate, \Illuminate\Support\Carbon::parse($endDate)->endOfDay()]);
        }
        $closing = $closingQuery->count();
        $totalRevenue = $closingQuery->sum('gross_revenue');

        // Average response time — computed in PHP for DB compatibility.
        // Basis: timestamp (lead masuk) → first_replied_at, atau last_update_at
        // (proxy waktu respon) untuk data migrasi yang tidak punya first_replied_at.
        $repliedLeads = (clone $query)
            ->where('status_fu', '!=', 'new')
            ->whereNotNull('last_update_at')
            ->select('timestamp', 'last_update_at', 'first_replied_at')
            ->get();
        $totalMinutes = 0;
        $count = $repliedLeads->count();
        foreach ($repliedLeads as $l) {
            $end = $l->first_replied_at ?? $l->last_update_at;
            $totalMinutes += $l->timestamp->diffInMinutes($end);
        }

        return [
            'total' => $total,
            'followed_up' => $followedUp,
            'not_followed_up' => $notFollowedUp,
            'closing' => $closing,
            'total_revenue' => $totalRevenue,
            'conversion_rate' => $total > 0 ? round(($closing / $total) * 100, 2) : 0,
            'avg_response_time_minutes' => $count > 0 ? round($totalMinutes / $count) : null,
        ];
    }
}
