<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class CallCampaign extends Model
{
    protected $fillable = [
        'user_id',
        'audience_id',
        'name',
        'status',
        'message_one',
        'message_two',
        'message_three',
        'delay_two_minutes',
        'delay_three_minutes',
        'starts_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function audience(): BelongsTo
    {
        return $this->belongsTo(Audience::class, 'audience_id', 'audience_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(CallCampaignLead::class);
    }

    public function messages(): HasManyThrough
    {
        return $this->hasManyThrough(CallCampaignLeadMessage::class, CallCampaignLead::class);
    }
}
