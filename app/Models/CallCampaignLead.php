<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CallCampaignLead extends Model
{
    protected $fillable = [
        'call_campaign_id',
        'audience_list_id',
        'call_status_id',
        'recipient',
        'first_name',
        'last_name',
        'title',
        'company',
        'location',
        'connection_id',
        'public_identifier',
        'profile_url',
        'member_urn',
        'status',
        'last_message_sent_at',
    ];

    protected $casts = [
        'last_message_sent_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(CallCampaign::class, 'call_campaign_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CallCampaignLeadMessage::class);
    }

    public function callStatus(): BelongsTo
    {
        return $this->belongsTo(CallStatus::class, 'call_status_id');
    }
}
