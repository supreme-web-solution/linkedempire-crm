<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallCampaignLeadMessage extends Model
{
    protected $fillable = [
        'call_campaign_lead_id',
        'step',
        'message_template',
        'scheduled_at',
        'sent_at',
        'status',
        'sent_message',
        'error_message',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CallCampaignLead::class, 'call_campaign_lead_id');
    }
}
