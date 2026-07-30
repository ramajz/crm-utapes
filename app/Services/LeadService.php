<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LeadService
{
    const FINANCIAL_STATUS_CLOSING = 'paid';

    public function __construct(
        protected UtmParserService $utmParser,
        protected LoyaltyService $loyaltyService,
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
                'handler_id' => $validated['handler_id'] ?? null,
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
            ]);

            return $lead;
        });
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
            ]);

            return $lead->fresh();
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
        $closing = (clone $query)->closingStatus()->count();
        $totalRevenue = (clone $query)->sum('total_value');

        // Average response time — computed in PHP for DB compatibility
        $repliedLeads = (clone $query)
            ->whereNotNull('first_replied_at')
            ->select('created_at', 'first_replied_at')
            ->get();
        $totalMinutes = 0;
        $count = $repliedLeads->count();
        foreach ($repliedLeads as $l) {
            $totalMinutes += $l->created_at->diffInMinutes($l->first_replied_at);
        }

        return [
            'total' => $total,
            'followed_up' => $followedUp,
            'not_followed_up' => $notFollowedUp,
            'closing' => $closing,
            'total_revenue' => $totalRevenue,
            'conversion_rate' => $total > 0 ? round(($closing / $total) * 100, 1) : 0,
            'avg_response_time_minutes' => $count > 0 ? round($totalMinutes / $count) : null,
        ];
    }
}
