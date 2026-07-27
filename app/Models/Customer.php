<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'phone',
        'name',
        'total_orders',
        'total_spend',
        'first_purchase_at',
        'last_purchase_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_orders' => 'integer',
            'total_spend' => 'integer',
            'first_purchase_at' => 'datetime',
            'last_purchase_at' => 'datetime',
        ];
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }
}
