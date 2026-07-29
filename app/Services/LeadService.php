<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadHistory;
use Illuminate\Support\Facades\DB;

class LeadService
{
    public function __construct(
        protected UtmParserService $utmParser,
        protected LoyaltyService $loyaltyService,
    ) {}

    /**
     * Create a new lead from webhook or CSV import.
     */
    public function createFromData(array $data): Lead
    {
        return DB::transaction(function () use ($data) {
            // Find or create customer
            $customer = $this->loyaltyService->findOrCreate(
                $data['phone'],
                $data['customer_name'],
                $data['total_value'] ?? 0
            );

            // Parse traffic type from UTM
            $trafficType = $this->utmParser->classify(
                $data['utm_source'] ?? null,
                $data['utm_medium'] ?? null
            );

            // Create lead
            $lead = Lead::create([
                'order_id' => $data['order_id'],
                'customer_id' => $customer->id,
                'handler_id' => $data['handler_id'] ?? null,
                'financial_status' => $data['financial_status'] ?? 'unpaid',
                'total_value' => $data['total_value'] ?? 0,
                'funnel_stage' => 'cold',
                'status_fu' => 'new',
                'notes' => $data['notes'] ?? null,
                'size' => $data['size'] ?? null,
                'utm_source' => $data['utm_source'] ?? null,
                'utm_medium' => $data['utm_medium'] ?? null,
                'utm_campaign' => $data['utm_campaign'] ?? null,
                'utm_content' => $data['utm_content'] ?? null,
                'traffic_type' => $trafficType,
                'lead_type' => $customer->total_orders > 1 ? 'repeat' : 'new',
                'timestamp' => $data['timestamp'] ?? now(),
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
                $newFinancialStatus = 'paid';
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
            if (isset($data['notes']) && $lead->notes !== $data['notes']) {
                $changes[] = [
                    'lead_id' => $lead->id,
                    'user_id' => $userId,
                    'field_changed' => 'notes',
                    'old_value' => $lead->notes,
                    'new_value' => $data['notes'],
                ];
            }
            if (isset($data['size']) && $lead->size !== $data['size']) {
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
        $closing = (clone $query)->closing()->count();
        $totalRevenue = (clone $query)->sum('total_value');

        // Average response time
        $avgResponseTime = (clone $query)
            ->whereNotNull('first_replied_at')
            ->select(DB::raw('AVG(EXTRACT(EPOCH FROM (first_replied_at - created_at)) / 60) as avg_time'))
            ->value('avg_time');

        return [
            'total' => $total,
            'followed_up' => $followedUp,
            'not_followed_up' => $notFollowedUp,
            'closing' => $closing,
            'total_revenue' => $totalRevenue,
            'conversion_rate' => $total > 0 ? round(($closing / $total) * 100, 1) : 0,
            'avg_response_time_minutes' => $avgResponseTime ? round($avgResponseTime) : null,
        ];
    }
}
