<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_id',
        'customer_id',
        'handler_id',
        'status',
        'payment_status',
        'is_probably_spam',
        'source',
        'created_time',
        'draft_time',
        'pending_time',
        'confirmed_time',
        'in_process_time',
        'ready_time',
        'shipped_time',
        'completed_time',
        'rts_time',
        'canceled_time',
        'closed_time',
        'unpaid_time',
        'paid_time',
        'conflict_time',
        'settled_time',
        'transfer_time',
        'payment_method',
        'epayment_provider',
        'financial_entity',
        'payment_account_holder',
        'payment_account_number',
        'transferproof_url',
        'pg_reference_id',
        'pg_payment_info',
        'gross_revenue',
        'scalev_fee',
        'payment_fee',
        'net_payment_revenue',
        'unique_code_discount',
        'discount_code_discount',
        'net_revenue',
        'product_price',
        'product_discount',
        'other_income',
        'cogs',
        'shipping_cost',
        'shipping_discount',
        'discount_rate',
        'total_quantity',
        'total_weight',
        'advertiser',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_id',
        'traffic_type',
        'store',
        'destination',
        'notes',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'is_probably_spam' => 'boolean',
            'created_time' => 'datetime',
            'draft_time' => 'datetime',
            'pending_time' => 'datetime',
            'confirmed_time' => 'datetime',
            'in_process_time' => 'datetime',
            'ready_time' => 'datetime',
            'shipped_time' => 'datetime',
            'completed_time' => 'datetime',
            'rts_time' => 'datetime',
            'canceled_time' => 'datetime',
            'closed_time' => 'datetime',
            'unpaid_time' => 'datetime',
            'paid_time' => 'datetime',
            'conflict_time' => 'datetime',
            'settled_time' => 'datetime',
            'transfer_time' => 'datetime',
            'gross_revenue' => 'decimal:0',
            'scalev_fee' => 'decimal:0',
            'payment_fee' => 'decimal:0',
            'net_payment_revenue' => 'decimal:0',
            'unique_code_discount' => 'decimal:0',
            'discount_code_discount' => 'decimal:0',
            'net_revenue' => 'decimal:0',
            'product_price' => 'decimal:0',
            'product_discount' => 'decimal:0',
            'other_income' => 'decimal:0',
            'cogs' => 'decimal:0',
            'shipping_cost' => 'decimal:0',
            'shipping_discount' => 'decimal:0',
            'discount_rate' => 'decimal:2',
            'destination' => 'array',
            'pg_payment_info' => 'array',
            'raw_payload' => 'array',
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

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function lead()
    {
        return $this->hasOne(Lead::class, 'order_id', 'order_id');
    }
}
