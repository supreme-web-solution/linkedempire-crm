<?php

namespace App\Http\Controllers;

use App\Helpers\CampaignHelper;
use App\Models\Audience;
use App\Models\AudienceList;
use App\Models\CallCampaign;
use App\Models\CallCampaignLead;
use App\Models\CallCampaignLeadMessage;
use App\Models\CallStatus;
use App\Services\CalendarLinkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CallCampaignController extends Controller
{
    use CampaignHelper;

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'audience_id' => ['required', 'integer'],
            'message_one' => ['required', 'string'],
            'message_two' => ['required', 'string'],
            'message_three' => ['required', 'string'],
            'delay_two_minutes' => ['required', 'integer', 'min:0'],
            'delay_three_minutes' => ['required', 'integer', 'min:0'],
        ]);

        $audience = Audience::where('user_id', Auth::id())
            ->where('audience_id', $data['audience_id'])
            ->firstOrFail();

        $campaign = CallCampaign::create([
            'user_id' => Auth::id(),
            'audience_id' => $audience->audience_id,
            'name' => $data['name'],
            'status' => 'active',
            'message_one' => $data['message_one'],
            'message_two' => $data['message_two'],
            'message_three' => $data['message_three'],
            'delay_two_minutes' => $data['delay_two_minutes'],
            'delay_three_minutes' => $data['delay_three_minutes'],
            'starts_at' => now(),
        ]);

        $startAt = $campaign->starts_at;
        $delayTwo = (int) $campaign->delay_two_minutes;
        $delayThree = (int) $campaign->delay_three_minutes;

        AudienceList::where('audience_id', $audience->audience_id)
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($campaign, $startAt, $delayTwo, $delayThree) {
                foreach ($rows as $row) {
                    if (empty($row->con_public_identifier)) {
                        continue;
                    }

                    $recipient = trim("{$row->con_first_name} {$row->con_last_name}");

                    $callStatus = CallStatus::create([
                        'user_id' => Auth::id(),
                        'recipient' => $recipient,
                        'profile' => $row->con_profile_url ?? $row->con_public_identifier,
                        'company' => $row->con_company_name,
                        'job_title' => $row->con_job_title,
                        'location' => $row->con_location,
                        'sequence' => $campaign->name,
                        'call_status' => 'campaign_active',
                        'original_message' => $campaign->message_one,
                        'linkedin_profile_url' => $row->con_profile_url,
                        'connection_id' => $row->con_id,
                        'campaign_name' => $campaign->name,
                        'conversation_history' => json_encode([]),
                        'last_interaction_at' => now(),
                        'interaction_count' => 0,
                    ]);

                    $lead = CallCampaignLead::create([
                        'call_campaign_id' => $campaign->id,
                        'audience_list_id' => $row->id,
                        'call_status_id' => $callStatus->id,
                        'recipient' => $recipient,
                        'first_name' => $row->con_first_name,
                        'last_name' => $row->con_last_name,
                        'title' => $row->con_job_title,
                        'company' => $row->con_company_name,
                        'location' => $row->con_location,
                        'connection_id' => $row->con_id,
                        'public_identifier' => $row->con_public_identifier,
                        'profile_url' => $row->con_profile_url,
                        'member_urn' => $row->con_member_urn,
                        'status' => 'active',
                    ]);

                    CallCampaignLeadMessage::create([
                        'call_campaign_lead_id' => $lead->id,
                        'step' => 1,
                        'message_template' => $campaign->message_one,
                        'scheduled_at' => $startAt->copy(),
                        'status' => 'pending',
                    ]);

                    CallCampaignLeadMessage::create([
                        'call_campaign_lead_id' => $lead->id,
                        'step' => 2,
                        'message_template' => $campaign->message_two,
                        'scheduled_at' => $startAt->copy()->addMinutes($delayTwo),
                        'status' => 'pending',
                    ]);

                    CallCampaignLeadMessage::create([
                        'call_campaign_lead_id' => $lead->id,
                        'step' => 3,
                        'message_template' => $campaign->message_three,
                        'scheduled_at' => $startAt->copy()->addMinutes($delayTwo + $delayThree),
                        'status' => 'pending',
                    ]);
                }
            });

        notify()->success('Call campaign created successfully');
        return redirect()->route('calls');
    }

    public function updateStatus(Request $request, $id)
    {
        $data = $request->validate([
            'status' => ['required', 'in:active,paused,completed'],
        ]);

        $campaign = CallCampaign::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $campaign->update(['status' => $data['status']]);

        notify()->success('Call campaign status updated');
        return redirect()->route('calls');
    }

    public function destroy($id)
    {
        $campaign = CallCampaign::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $leadIds = $campaign->leads()->pluck('id');

        CallCampaignLeadMessage::whereIn('call_campaign_lead_id', $leadIds)->delete();
        CallCampaignLead::where('call_campaign_id', $campaign->id)->delete();
        $campaign->delete();

        notify()->success('Call campaign deleted');
        return redirect()->route('calls');
    }

    public function readyToSend(Request $request)
    {
        try {
            $user = $this->checkAuthorization($request);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage(),
                'status' => 400,
            ], 400);
        }

        $limit = (int) $request->query('limit', 10);
        $limit = $limit <= 0 ? 10 : min($limit, 50);

        $messages = CallCampaignLeadMessage::where('status', 'pending')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->whereHas('lead', function ($query) use ($user) {
                $query->where('status', 'active')
                    ->whereHas('campaign', function ($campaignQuery) use ($user) {
                        $campaignQuery->where('user_id', $user->id)
                            ->where('status', 'active');
                    });
            })
            ->with(['lead.campaign', 'lead.callStatus'])
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get();

        $payload = $messages->map(function (CallCampaignLeadMessage $message) use ($user) {
            $lead = $message->lead;

            return [
                'id' => $message->id,
                'step' => $message->step,
                'campaign_id' => $lead->campaign->id,
                'lead' => [
                    'id' => $lead->id,
                    'name' => $lead->recipient,
                    'firstName' => $lead->first_name,
                    'lastName' => $lead->last_name,
                    'title' => $lead->title,
                    'connectionId' => $lead->public_identifier,
                    'publicIdentifier' => $lead->public_identifier,
                    'profileUrl' => $lead->profile_url,
                ],
                'message' => $this->buildMessage($message, $user),
            ];
        });

        return response()->json([
            'success' => true,
            'messages' => $payload,
        ]);
    }

    public function updateMessageStatus(Request $request, $id)
    {
        try {
            $user = $this->checkAuthorization($request);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage(),
                'status' => 400,
            ], 400);
        }

        $data = $request->validate([
            'status' => ['required', 'in:sent,failed,skipped'],
            'sent_message' => ['nullable', 'string'],
            'error_message' => ['nullable', 'string'],
        ]);

        $message = CallCampaignLeadMessage::with(['lead.campaign', 'lead.callStatus'])
            ->findOrFail($id);

        if ($message->lead->campaign->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $update = [
            'status' => $data['status'],
            'sent_message' => $data['sent_message'] ?? null,
            'error_message' => $data['error_message'] ?? null,
        ];

        if ($data['status'] === 'sent') {
            $update['sent_at'] = now();
        }

        $message->update($update);

        $lead = $message->lead;
        if ($data['status'] === 'sent') {
            $lead->last_message_sent_at = now();
            if ($message->step === 3) {
                $lead->status = 'completed';
            }
            $lead->save();

            $this->updateCallStatusHistory($lead->callStatus, $message, $data['sent_message']);

            if ($message->step === 3) {
                $pendingExists = CallCampaignLeadMessage::whereHas('lead', function ($query) use ($lead) {
                    $query->where('call_campaign_id', $lead->call_campaign_id);
                })->where('status', 'pending')->exists();

                if (!$pendingExists) {
                    $lead->campaign->update(['status' => 'completed']);
                }
            }
        }

        if (in_array($data['status'], ['failed', 'skipped'], true)) {
            $lead->status = 'failed';
            $lead->save();

            CallCampaignLeadMessage::where('call_campaign_lead_id', $lead->id)
                ->where('status', 'pending')
                ->where('step', '>', $message->step)
                ->update([
                    'status' => 'skipped',
                    'error_message' => 'previous_step_failed',
                ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Call campaign message updated',
        ]);
    }

    private function buildMessage(CallCampaignLeadMessage $message, $user): string
    {
        $template = $message->message_template ?? '';

        return $template;
    }

    private function updateCallStatusHistory(?CallStatus $callStatus, CallCampaignLeadMessage $message, ?string $sentMessage): void
    {
        if (!$callStatus) {
            return;
        }

        $conversationHistory = json_decode($callStatus->conversation_history ?? '[]', true) ?: [];
        $conversationHistory[] = [
            'type' => 'campaign_message',
            'message' => $sentMessage ?? $message->message_template ?? '',
            'timestamp' => now()->toISOString(),
            'step' => $message->step,
            'campaign_id' => $message->lead->call_campaign_id,
        ];

        $callStatus->update([
            'conversation_history' => json_encode($conversationHistory),
            'last_interaction_at' => now(),
            'interaction_count' => ($callStatus->interaction_count ?? 0) + 1,
        ]);
    }
}
