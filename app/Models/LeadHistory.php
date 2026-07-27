<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadHistory extends Model
{
    const UPDATED_AT = null; // We only track created_at

    protected $fillable = [
        'lead_id',
        'user_id',
        'field_changed',
        'old_value',
        'new_value',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
