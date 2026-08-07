<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppTemplate extends Model
{
    const CATEGORIES = ['cold', 'warm', 'hot'];

    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'name',
        'category',
        'message',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
