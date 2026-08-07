<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use SoftDeletes;

    const STATUSES = [
        'new', 'chatted', 'replied', 'interested',
        'nunggu_gajian', 'promise_transfer', 'closing', 'rejected',
    ];

    const FUNNEL_MAP = [
        'new' => 'cold',
        'chatted' => 'cold',
        'rejected' => 'cold',
        'replied' => 'warm',
        'interested' => 'warm',
        'nunggu_gajian' => 'hot',
        'promise_transfer' => 'hot',
        'closing' => 'hot',
    ];

    protected $fillable = [
        'order_id',
        'customer_id',
        'handler_id',
        'branch_id',
        'financial_status',
        'total_value',
        'funnel_stage',
        'status_fu',
        'notes',
        'size',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'traffic_type',
        'lead_type',
        'first_replied_at',
        'last_update_at',
        'follow_up_required',
        'follow_up_status',
        'follow_up_completed_at',
        'timestamp',
    ];

    protected function casts(): array
    {
        return [
            'total_value' => 'integer',
            'timestamp' => 'datetime',
            'first_replied_at' => 'datetime',
            'last_update_at' => 'datetime',
            'follow_up_required' => 'boolean',
            'follow_up_completed_at' => 'datetime',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function handler()
    {
        return $this->belongsTo(Handler::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function histories()
    {
        return $this->hasMany(LeadHistory::class);
    }

    // Scopes
    public function scopeByHandler($query, $handlerId)
    {
        return $query->where('handler_id', $handlerId);
    }

    public function scopeByDateRange($query, $start, $end)
    {
        return $query->whereBetween('timestamp', [$start, \Carbon\Carbon::parse($end)->endOfDay()]);
    }

    public function scopeNotFollowedUp($query)
    {
        return $query->where('status_fu', 'new');
    }

    public function scopeFollowUpRequired($query)
    {
        return $query->where('follow_up_required', true);
    }

    public function scopeFollowUpPending($query)
    {
        return $query->where('follow_up_status', 'pending');
    }

    public function scopeFollowedUp($query)
    {
        return $query->where('status_fu', '!=', 'new');
    }

    public function scopeClosingStatus($query)
    {
        return $query->where('financial_status', 'paid');
    }

    public function scopeHot($query)
    {
        return $query->where('funnel_stage', 'hot');
    }

    // Accessors
    public function getResponseTimeMinutesAttribute(): ?int
    {
        if ($this->first_replied_at && $this->created_at) {
            return $this->created_at->diffInMinutes($this->first_replied_at);
        }
        return null;
    }

    public function getFormattedTotalAttribute(): string
    {
        return 'Rp ' . number_format($this->total_value, 0, ',', '.');
    }

    // Static Helpers
    public static function mapStatusToFunnel(string $statusFu): string
    {
        return self::FUNNEL_MAP[$statusFu] ?? 'cold';
    }

    public static function isClosingStatus(string $statusFu): bool
    {
        return $statusFu === 'closing';
    }
}
