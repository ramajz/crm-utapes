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
            'total_spend' => 'decimal:0',
            'first_purchase_at' => 'datetime',
            'last_purchase_at' => 'datetime',
        ];
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function getWaNumberAttribute(): ?string
    {
        $digits = preg_replace('/[^0-9]/', '', $this->phone ?? '');

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '62')) {
            // already correct
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        return $digits !== '' ? $digits : null;
    }
}
