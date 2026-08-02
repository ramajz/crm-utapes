<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'position',
        'product_name',
        'variant_unique_id',
        'variant_sku',
        'quantity',
        'weight',
        'product_price',
        'variant_price',
        'variant_cogs',
        'discount',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'weight' => 'integer',
            'product_price' => 'decimal:0',
            'variant_price' => 'decimal:0',
            'variant_cogs' => 'decimal:0',
            'discount' => 'decimal:0',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
