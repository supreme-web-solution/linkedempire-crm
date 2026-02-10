<?php

namespace App\Http\Controllers;

use App\Models\CallStatus;
use App\Models\CallReminder;
use App\Models\CallReminderMessage;
use App\Models\CallCampaign;
use App\Models\Audience;
use App\Models\User;
use App\Helpers\CampaignHelper;
use App\Services\ChatGPT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use App\Services\CalendarLinkService;

class CallManagerController extends Controller
{
    use CampaignHelper;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $callStatus = CallStatus::where('user_id', Auth::id())->paginate(15);
        $callCampaigns = CallCampaign::where('user_id', Auth::id())
            ->with('audience')
            ->withCount('leads')
            ->withCount(['messages as messages_sent_count' => function ($query) {
                $query->where('call_campaign_lead_messages.status', 'sent');
            }])
            ->latest()
            ->get();
        $audiences = Audience::where('user_id', Auth::id())
            ->orderBy('audience_name')
            ->get();

        return view('callmanager.index', compact('callStatus', 'callCampaigns', 'audiences'));
    }

    /**
     * Display AI-powered call management dashboard
     */
    public function aiDashboard()
    {
        $userId = Auth::id();
        
        // Get statistics
        $totalCalls = CallStatus::where('user_id', $userId)->count();
        $hotLeads = CallStatus::where('user_id', $userId)->where('lead_category', 'hot_lead')->count();
        $warmLeads = CallStatus::where('user_id', $userId)->where('lead_category', 'warm_lead')->count();
        $aiMessages = CallStatus::where('user_id', $userId)->whereNotNull('original_message')->count();
        
        // Get recent calls with AI data
        $recentCalls = CallStatus::where('user_id', $userId)
            ->with('campaign')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
        
        return view('callmanager.ai-dashboard', compact(
            'totalCalls', 'hotLeads', 'warmLeads', 'aiMessages', 'recentCalls'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function callReminder()
    {
        // Show scheduled calls that can have reminders
        $scheduledCalls = CallStatus::where('user_id', Auth::id())
            ->where('call_status', 'scheduled')
            ->whereNotNull('scheduled_time')
            ->orderBy('scheduled_time', 'asc')
            ->paginate(15);

        return view('callmanager.call-reminder', compact('scheduledCalls'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Get call reminder status for a specific call
        $call = CallStatus::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$call) {
            return response()->json(['error' => 'Call not found'], 404);
        }

        // Get reminder message record
        $reminderMessage = CallReminderMessage::where('call_reminder_id', $call->id)->first();

        $reminderData = [
            'call_id' => $call->id,
            'recipient' => $call->recipient,
            'scheduled_time' => $call->scheduled_time,
            'reminder_16_24_sent' => $call->reminder_16_24_sent,
            'reminder_2_hours_sent' => $call->reminder_2_hours_sent,
            'reminder_10_40_min_sent' => $call->reminder_10_40_min_sent,
            '16_24_hours_before_status' => $reminderMessage ? $reminderMessage->{'16_24_hours_before_status'} : false,
            '16_24_hours_before_message' => $reminderMessage ? $reminderMessage->{'16_24_hours_before_message'} : $this->getDefaultReminderMessage('16_24', $call),
            'couple_hours_before_status' => $reminderMessage ? $reminderMessage->couple_hours_before_status : false,
            'couple_hours_before_message' => $reminderMessage ? $reminderMessage->couple_hours_before_message : $this->getDefaultReminderMessage('2_hours', $call),
            '10_40_minutes_before_status' => $reminderMessage ? $reminderMessage->{'10_40_minutes_before_status'} : false,
            '10_40_minutes_before_message' => $reminderMessage ? $reminderMessage->{'10_40_minutes_before_message'} : $this->getDefaultReminderMessage('10_40_min', $call)
        ];

        return response()->json([
            'data' => $reminderData
        ]);
    }

    /**
     * Get default reminder message for a call
     */
    private function getDefaultReminderMessage($reminderType, $call)
    {
        $scheduledTime = \Carbon\Carbon::parse($call->scheduled_time);
        
        switch ($reminderType) {
            case '16_24':
                return "Hi {$call->recipient}, this is a friendly reminder that we have a call scheduled for {$scheduledTime->format('M j, Y \a\t g:i A')}. Looking forward to our conversation!";
                
            case '2_hours':
                return "Hi {$call->recipient}, just a quick reminder that our call is coming up in about 2 hours at {$scheduledTime->format('g:i A')}. See you soon!";
                
            case '10_40_min':
                return "Hi {$call->recipient}, our call is starting in about 30 minutes at {$scheduledTime->format('g:i A')}. I'll be ready to connect!";
                
            default:
                return "Hi {$call->recipient}, this is a reminder about our upcoming call at {$scheduledTime->format('M j, Y \a\t g:i A')}.";
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'call_status_id' => ['required'],
            'call_status' => ['required']
        ]);

        $callStatus = CallStatus::findOrFail($data['call_status_id']);

        $callStatus->update([
            'call_status' => $data['call_status']
        ]);

        notify()->success('Call status updated successfully');
        return redirect()->route('calls');
    }

    /**
     * Clear all call status rows for the current user.
     */
    public function clearCallStatus()
    {
        $userId = Auth::id();

        $callIds = CallStatus::where('user_id', $userId)->pluck('id');
        if ($callIds->isNotEmpty()) {
            CallReminderMessage::whereIn('call_reminder_id', $callIds)->delete();
        }

        CallStatus::where('user_id', $userId)->delete();

        notify()->success('Call status list cleared');
        return redirect()->route('calls');
    }

    public function updateCallReminder(Request $request)
    {
        // Update reminder settings for a specific call
        $call = CallStatus::where('id', $request->id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$call) {
            notify()->error('Call not found');
            return redirect()->route('calls.reminders');
        }

        // Update or create reminder message record
        $reminderMessage = CallReminderMessage::where('call_reminder_id', $call->id)->first();
        
        if (!$reminderMessage) {
            // Create new reminder message record
            $reminderMessage = CallReminderMessage::create([
                'call_reminder_id' => $call->id,
                '16_24_hours_before_status' => (bool)$request->input('16_24_hours_before_status', false),
                '16_24_hours_before_message' => $request->input('16_24_hours_before_message'),
                'couple_hours_before_status' => (bool)$request->input('couple_hours_before_status', false),
                'couple_hours_before_message' => $request->input('couple_hours_before_message'),
                '10_40_minutes_before_status' => (bool)$request->input('10_40_minutes_before_status', false),
                '10_40_minutes_before_message' => $request->input('10_40_minutes_before_message')
            ]);
        } else {
            // Update existing reminder message record
            $reminderMessage->update([
                '16_24_hours_before_status' => (bool)$request->input('16_24_hours_before_status', false),
                '16_24_hours_before_message' => $request->input('16_24_hours_before_message'),
                'couple_hours_before_status' => (bool)$request->input('couple_hours_before_status', false),
                'couple_hours_before_message' => $request->input('couple_hours_before_message'),
                '10_40_minutes_before_status' => (bool)$request->input('10_40_minutes_before_status', false),
                '10_40_minutes_before_message' => $request->input('10_40_minutes_before_message')
            ]);
        }


        notify()->success('Call reminder updated successfully');
        return redirect()->route('calls.reminders');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function storeCallStatus(Request $request)
    {
        try {
            $this->checkAuthorization($request);
        } catch (\Throwable $th) {
            return response()->json([
                "message" => $th->getMessage(),
                "status" => 400
            ],400);
        }

        $user = User::where('linkedin_id', $request->header('lk-id'))->first();

        // Generate/transform original message based on inputs
        $originalMessage = $request->original_message ?? null;
        $paraphraseUserMessage = filter_var($request->input('paraphrase_user_message', false), FILTER_VALIDATE_BOOLEAN);
        
        // AI mode and review time are now handled by the node model, not database
        
        Log::info('🔍 Call message generation debug', [
            'original_message_provided' => $originalMessage,
            'recipient' => $request->recipient,
            'company' => $request->company,
            'industry' => $request->industry,
            'will_generate_ai' => (!$originalMessage && $request->recipient)
        ]);
        
        if ($originalMessage && $paraphraseUserMessage) {
            Log::info('📝 Paraphrasing user-provided message');
            try {
                $chatGPT = new ChatGPT();
                $prompt = "Paraphrase the following outreach message to be concise, friendly, and professional. Keep intent and meaning, improve clarity, and limit to ~80-120 words. Return only the rewritten message.\n\nMESSAGE:\n" . $originalMessage;
                $result = $chatGPT->generateContent($prompt);
                $originalMessage = trim($result['content']);
                Log::info('✅ Paraphrase completed');
            } catch (\Throwable $th) {
                Log::warning('⚠️ Paraphrase failed, using original as-is', ['error' => $th->getMessage()]);
            }
        } elseif (!$originalMessage && $request->recipient) {
            Log::info('🤖 Generating AI call message', [
                'recipient' => $request->recipient,
                'company' => $request->company,
                'industry' => $request->industry
            ]);
            
            try {
                $chatGPT = new ChatGPT();
                $aiResult = $chatGPT->generateCallMessage(
                    $request->recipient,
                    $request->company ?? null,
                    $request->industry ?? null
                );
                $originalMessage = $aiResult['content'];
                
                Log::info('✅ AI message generated successfully', [
                    'ai_message' => $originalMessage,
                    'ai_result' => $aiResult
                ]);
            } catch (\Throwable $th) {
                Log::error('❌ AI message generation failed', [
                    'error' => $th->getMessage(),
                    'trace' => $th->getTraceAsString()
                ]);
                
                // Fallback to default message
                $originalMessage = "Hi {$request->recipient}, I'd like to schedule a call to discuss how we can help your business grow. Are you available for a brief conversation this week?";
                
                Log::info('🔄 Using fallback message', [
                    'fallback_message' => $originalMessage
                ]);
            }
        } else {
            Log::info('⏭️ Skipping AI generation', [
                'reason' => $originalMessage ? 'original_message_provided' : 'no_recipient',
                'original_message' => $originalMessage,
                'recipient' => $request->recipient
            ]);
        }

        // Check if this is an acceptance update (don't reset existing data)
        $isAcceptanceUpdate = $request->input('is_acceptance_update', false);

        // Base attributes that should exist
        $attributes = [
            'recipient' => $request->recipient,
            'profile' => $request->profile,
            'sequence' => $request->sequence,
            'call_status' => $request->callStatus ?? 'pending_review',
            'user_id' => optional($user)->id,
        ];

        // Calculate scheduled send time for review mode (from node model)
        $scheduledSendAt = null;
        $aiMode = $request->input('ai_mode', 'auto');
        $reviewTime = $request->input('review_time', null);
        
        if ($aiMode === 'review' && $reviewTime) {
            $scheduledSendAt = now()->addMinutes($reviewTime);
        }

        // Conditionally include optional fields if columns exist
        $optionalFields = [
            'company' => $request->company,
            'industry' => $request->industry,
            'job_title' => $request->job_title,
            'location' => $request->location,
            'linkedin_profile_url' => $request->linkedin_profile_url,
            'connection_id' => $request->connection_id,
            'conversation_urn_id' => $request->conversation_urn_id,
            'original_message' => $originalMessage,
            'campaign_id' => $request->campaign_id,
            'campaign_name' => $request->campaign_name ?? $request->sequence,
            'conversation_history' => json_encode([]), // Initialize empty conversation history
            'last_interaction_at' => now(),
            'interaction_count' => 1,
            'call_status' => 'pending_review',
            'scheduled_send_at' => $scheduledSendAt,
        ];

        foreach ($optionalFields as $column => $value) {
            if (Schema::hasColumn('call_status', $column)) {
                $attributes[$column] = $value;
            } else {
                Log::warning("⚠️ Column '{$column}' does not exist in call_status table", [
                    'column' => $column,
                    'value' => $value
                ]);
            }
        }

        Log::info('🔍 CallStatus creation debug', [
            'attributes' => $attributes,
            'original_message' => $originalMessage
        ]);

        // Upsert behavior: if a record already exists for this user and connection, update instead of create
        $existing = null;
        if (!empty($attributes['user_id'])) {
            $existing = CallStatus::when(!empty($attributes['connection_id'] ?? null), function ($q) use ($attributes) {
                    $q->where('connection_id', $attributes['connection_id']);
                })
                ->when(!empty($attributes['conversation_urn_id'] ?? null), function ($q) use ($attributes) {
                    $q->orWhere('conversation_urn_id', $attributes['conversation_urn_id']);
                })
                ->where('user_id', $attributes['user_id'])
                ->first();
        }

        if ($existing) {
            // Overwrite like creating anew, but on the same row
            $updateData = $attributes;

            // If this is an acceptance update, PRESERVE existing conversation data
            if ($isAcceptanceUpdate) {
                Log::info('🔒 Acceptance update detected - preserving existing conversation data', [
                    'call_id' => $existing->id,
                    'connection_id' => $existing->connection_id
                ]);
                
                // Don't reset these fields if it's just an acceptance check
                $fieldsToPreserve = [
                    'conversation_history',
                    'ai_analysis',
                    'calendar_link',
                    'scheduled_time',
                    'meeting_link',
                    'lead_category',
                    'lead_score',
                    'original_message',
                    'pending_message'
                ];
                
                foreach ($fieldsToPreserve as $field) {
                    if (isset($updateData[$field])) {
                        unset($updateData[$field]);
                    }
                }
                
                // Keep existing interaction count and just update the timestamp
                if (Schema::hasColumn('call_status', 'last_interaction_at')) {
                    $updateData['last_interaction_at'] = now();
                }
                if (Schema::hasColumn('call_status', 'interaction_count')) {
                    unset($updateData['interaction_count']); // Don't reset counter
                }
            } else {
                // This is a NEW campaign/call - reset conversation fields for a fresh start
                Log::info('🆕 New campaign detected - resetting conversation data', [
                    'call_id' => $existing->id,
                    'connection_id' => $existing->connection_id
                ]);
                
                if (Schema::hasColumn('call_status', 'conversation_history')) {
                    $updateData['conversation_history'] = json_encode([]);
                }
                if (Schema::hasColumn('call_status', 'ai_analysis')) {
                    $updateData['ai_analysis'] = null;
                }
                if (Schema::hasColumn('call_status', 'calendar_link')) {
                    $updateData['calendar_link'] = null;
                }
                if (Schema::hasColumn('call_status', 'scheduled_time')) {
                    $updateData['scheduled_time'] = null;
                }
                if (Schema::hasColumn('call_status', 'meeting_link')) {
                    $updateData['meeting_link'] = null;
                }
                if (Schema::hasColumn('call_status', 'lead_category')) {
                    $updateData['lead_category'] = null;
                }
                if (Schema::hasColumn('call_status', 'lead_score')) {
                    $updateData['lead_score'] = null;
                }
                if (Schema::hasColumn('call_status', 'last_interaction_at')) {
                    $updateData['last_interaction_at'] = now();
                }
                if (Schema::hasColumn('call_status', 'interaction_count')) {
                    $updateData['interaction_count'] = 1;
                }

                // Ensure we keep identifiers up to date if provided
                // original_message: use newly generated/provided when present
                if (empty($updateData['original_message'])) {
                    // If no new original_message was generated, keep existing one
                    unset($updateData['original_message']);
                }
            }

            $existing->update($updateData);
            $callStatus = $existing;
            Log::info('🔁 Updated existing CallStatus by connection/conversation for user', [
                'call_id' => $existing->id,
                'connection_id' => $existing->connection_id,
                'conversation_urn_id' => $existing->conversation_urn_id,
                'is_acceptance_update' => $isAcceptanceUpdate
            ]);
        } else {
            $callStatus = CallStatus::create($attributes);
        }

        Log::info('✅ CallStatus created', [
            'call_id' => $callStatus->id,
            'original_message_saved' => $callStatus->original_message,
            'conversation_history_initialized' => $callStatus->conversation_history,
            'connection_id' => $callStatus->connection_id
        ]);

        return response()->json([
            'message' => $existing ? 'Call status updated successfully' : 'Call status created successfully',
            'call_id' => $callStatus->id
        ], $existing ? 200 : 201);
    }

    /**
     * Generate AI-powered call booking message
     */
    public function generateCallMessage(Request $request)
    {
        $data = $request->validate([
            'recipient_name' => 'required|string',
            'company' => 'nullable|string',
            'industry' => 'nullable|string'
        ]);

        try {
            $chatGPT = new ChatGPT();
            $result = $chatGPT->generateCallMessage(
                $data['recipient_name'],
                $data['company'] ?? null,
                $data['industry'] ?? null
            );

            return response()->json([
                'message' => $result['content'],
                'words' => $result['words']
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed to generate AI message: ' . $th->getMessage()
            ], 422);
        }
    }

    /**
     * Analyze a message reply with AI using full conversation context
     */
    public function analyzeMessageReply(Request $request)
    {
        $data = $request->validate([
            'message' => 'required|string',
            'leadName' => 'required|string',
            'context' => 'nullable|string',
            'original_message' => 'nullable|string',
            'call_id' => 'nullable|string',
            'connection_id' => 'nullable|string',
            'conversation_urn_id' => 'nullable|string'
        ]);

        try {
            // Get original message and conversation thread with robust lookup strategy
            $originalMessage = $data['original_message'] ?? null;
            $conversationThread = [];
            $call = null;
            
            if (!$originalMessage) {
                // Try multiple lookup strategies
                if ($data['call_id']) {
                    // First try: direct ID lookup
                    if (is_numeric($data['call_id'])) {
                        $call = CallStatus::where('id', $data['call_id'])->first();
                    }
                    
                    // Second try: treat call_id as connection_id (for extension temporary IDs)
                    if (!$call && !is_numeric($data['call_id'])) {
                        $call = CallStatus::where('connection_id', $data['call_id'])->first();
                    }
                }
                
                // Third try: explicit connection_id parameter
                if (!$call && $data['connection_id']) {
                    $call = CallStatus::where('connection_id', $data['connection_id'])->first();
                }
                
                // Fourth try: conversation_urn_id parameter
                if (!$call && $data['conversation_urn_id']) {
                    $call = CallStatus::where('conversation_urn_id', $data['conversation_urn_id'])->first();
                }
                
                if ($call) {
                    $originalMessage = $call->original_message ?? 'No original message available';
                    
                    // Get conversation thread for enhanced analysis
                    $conversationThread = json_decode($call->conversation_history ?? '[]', true) ?: [];
                    
                    Log::info('✅ Found call record for enhanced analysis:', [
                        'id' => $call->id,
                        'connection_id' => $call->connection_id,
                        'conversation_urn_id' => $call->conversation_urn_id,
                        'has_original_message' => !empty($call->original_message),
                        'conversation_count' => count($conversationThread)
                    ]);
                } else {
                    Log::warning('⚠️ No call record found for analysis', [
                        'call_id' => $data['call_id'],
                        'connection_id' => $data['connection_id'],
                        'conversation_urn_id' => $data['conversation_urn_id']
                    ]);
                }
            }
            
            // Analyze reply with AI using enhanced conversation context
            $chatGPT = new ChatGPT();
            $context = $data['context'] ?? 'LinkedIn message response analysis';
            
            // Use enhanced conversation analysis if we have conversation history
            if (!empty($conversationThread)) {
                Log::info('🧠 Using enhanced conversation thread analysis', [
                    'conversation_count' => count($conversationThread),
                    'lead_name' => $data['leadName']
                ]);
                
                $analysis = $chatGPT->analyzeConversationThread(
                    $conversationThread,
                    $originalMessage ?: 'No original message available',
                    $data['message'],
                    $data['leadName']
                );
                
                // Map the enhanced analysis to the expected format for backward compatibility
                $mappedAnalysis = [
                    'intent' => $analysis['current_intent'] ?? 'unknown',
                    'sentiment' => $this->extractSentimentFromAnalysis($analysis),
                    'next_action' => $analysis['next_action'] ?? 'follow_up_later',
                    'suggested_response' => $analysis['suggested_response'] ?? 'Thank you for your response. I\'ll follow up with you soon.',
                    'lead_score' => $analysis['lead_score'] ?? 5,
                    'is_positive' => $analysis['is_positive'] ?? false,
                    'reasoning' => $analysis['reasoning'] ?? 'Enhanced conversation analysis',
                    
                    // Additional enhanced fields
                    'conversation_summary' => $analysis['conversation_summary'] ?? '',
                    'lead_communication_style' => $analysis['lead_communication_style'] ?? '',
                    'engagement_pattern' => $analysis['engagement_pattern'] ?? '',
                    'underlying_concerns' => $analysis['underlying_concerns'] ?? '',
                    'sentiment_evolution' => $analysis['sentiment_evolution'] ?? '',
                    'context_insights' => $analysis['context_insights'] ?? '',
                    'recommended_approach' => $analysis['recommended_approach'] ?? '',
                    'confidence_level' => $analysis['confidence_level'] ?? 'medium'
                ];
                
                Log::info('✅ Enhanced conversation analysis completed', [
                    'intent' => $mappedAnalysis['intent'],
                    'sentiment' => $mappedAnalysis['sentiment'],
                    'lead_score' => $mappedAnalysis['lead_score'],
                    'is_positive' => $mappedAnalysis['is_positive'],
                    'confidence_level' => $mappedAnalysis['confidence_level']
                ]);
                
                return response()->json([
                    'success' => true,
                    'analysis' => $mappedAnalysis,
                    'message' => 'Enhanced conversation analysis completed successfully',
                    'enhanced_analysis' => true
                ]);
            } else {
                // Fallback to original simple analysis if no conversation history
                Log::info('🔄 Using simple analysis (no conversation history)', [
                    'lead_name' => $data['leadName']
                ]);
                
                $originalMessageText = $originalMessage ?: 'Not provided - this appears to be the start of the conversation';
                
                // Use heredoc syntax for maximum reliability
                $analysisPrompt = <<<EOD
You are an expert LinkedIn conversation analyst. Your PRIMARY GOAL is to help schedule meetings with prospects. Analyze this message reply for call scheduling intent and lead qualification.

ORIGINAL CALL MESSAGE: {$originalMessageText}
REPLY MESSAGE: {$data['message']}
LEAD NAME: {$data['leadName']}
CONTEXT: {$context}

ANALYSIS INSTRUCTIONS:
Analyze the reply message by understanding the context, tone, and underlying intent. Look beyond simple keyword matching and focus on:

1. **Conversation Context**: First, understand how this reply relates to the original call message
   - What was the original purpose of the conversation?
   - How does this reply show progression in the conversation?
   - Is the person responding to the specific call request or going off-topic?

2. **Intent Analysis**: What is the person actually trying to communicate?
   - Are they expressing genuine interest in scheduling a call?
   - Are they politely declining or showing disinterest?
   - Are they asking for more information before deciding?
   - Are they suggesting alternative times or methods?
   - Are they being evasive or non-committal?

3. **Sentiment Analysis**: What is the emotional tone and attitude?
   - Positive: Enthusiastic, excited, eager, grateful
   - Neutral: Professional, matter-of-fact, cautious
   - Negative: Dismissive, frustrated, uninterested, annoyed

4. **Context Understanding**: Consider the full conversation context
   - How does this reply relate to the original call request?
   - Are there any subtle cues about their availability or interest level?
   - What might they be thinking or feeling based on their response?
   - Does their reply show they remember/understand the original message?

5. **Meeting Scheduling Focus**: Always look for opportunities to suggest or schedule meetings
   - If they show any interest, immediately suggest a meeting
   - If they have concerns, address them and then suggest a meeting
   - If they're hesitant, offer a brief meeting to discuss their concerns
   - If they're busy, suggest a quick 15-minute call
   - If they're interested, suggest a longer meeting to discuss details
   - If they're asking questions, answer them and then suggest a meeting to discuss further

6. **Actionable Insights**: What should be the next step to move toward scheduling?
   - If interested: How can we move forward with scheduling?
   - If hesitant: What information might help them decide about a meeting?
   - If declining: Is there a way to maintain the relationship and suggest a future meeting?
   - If asking questions: What do they need to know before agreeing to a meeting?

REQUIRED OUTPUT (JSON format only - NO OTHER TEXT):
{
  "intent": "available|interested|not_interested|needs_more_info|reschedule_request|busy|greeting|scheduling_request",
  "sentiment": "positive|neutral|negative",
  "next_action": "schedule_call|send_calendar|send_info|follow_up_later|end_conversation|ask_availability",
  "suggested_response": "Natural, human-like response that focuses on scheduling a meeting. No placeholders, brackets, or generic text. Be specific and personal to the lead.",
  "lead_score": 1-10,
  "is_positive": true|false,
  "reasoning": "Brief explanation of your analysis"
}

CRITICAL: Return ONLY valid JSON. No explanations, no markdown, no additional text. The response must be parseable JSON.

Focus on understanding the human behind the message and always look for opportunities to schedule meetings.
EOD;

                $aiAnalysis = $chatGPT->generateContent($analysisPrompt);
                $analysis = json_decode($aiAnalysis['content'], true);
                
                // Log the raw AI response for debugging
                Log::info('🤖 AI Analysis Raw Response (Simple)', [
                    'raw_content' => $aiAnalysis['content'],
                    'json_decode_result' => $analysis,
                    'json_last_error' => json_last_error_msg()
                ]);
                
                // Ensure we have the required fields
                if (!$analysis || json_last_error() !== JSON_ERROR_NONE) {
                    Log::warning('⚠️ AI Analysis failed or returned invalid JSON, using fallback', [
                        'raw_content' => $aiAnalysis['content'],
                        'json_error' => json_last_error_msg()
                    ]);
                    
                    $analysis = [
                        'intent' => 'unknown',
                        'sentiment' => 'neutral',
                        'leadScore' => 5,
                        'isPositive' => false,
                        'nextAction' => 'follow_up_later',
                        'suggestedResponse' => 'Thank you for your response. I\'ll follow up with you soon.'
                    ];
                } else {
                    // Validate that we have the required fields
                    $analysis = array_merge([
                        'intent' => 'unknown',
                        'sentiment' => 'neutral',
                        'leadScore' => 5,
                        'isPositive' => false,
                        'nextAction' => 'follow_up_later',
                        'suggestedResponse' => 'Thank you for your response. I\'ll follow up with you soon.'
                    ], $analysis);
                    
                    Log::info('✅ AI Analysis successful (Simple)', [
                        'intent' => $analysis['intent'],
                        'sentiment' => $analysis['sentiment'],
                        'leadScore' => $analysis['leadScore'],
                        'isPositive' => $analysis['isPositive']
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'analysis' => $analysis,
                    'message' => 'Analysis completed successfully',
                    'enhanced_analysis' => false
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Message analysis failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Analysis failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extract sentiment from enhanced analysis
     */
    private function extractSentimentFromAnalysis($analysis)
    {
        // Try to extract sentiment from various fields in the enhanced analysis
        $sentimentEvolution = $analysis['sentiment_evolution'] ?? '';
        $contextInsights = $analysis['context_insights'] ?? '';
        $reasoning = $analysis['reasoning'] ?? '';
        
        // Look for sentiment indicators in the text
        $positiveIndicators = ['positive', 'enthusiastic', 'excited', 'interested', 'eager', 'grateful'];
        $negativeIndicators = ['negative', 'dismissive', 'frustrated', 'uninterested', 'annoyed', 'declining'];
        
        $text = strtolower($sentimentEvolution . ' ' . $contextInsights . ' ' . $reasoning);
        
        foreach ($positiveIndicators as $indicator) {
            if (strpos($text, $indicator) !== false) {
                return 'positive';
            }
        }
        
        foreach ($negativeIndicators as $indicator) {
            if (strpos($text, $indicator) !== false) {
                return 'negative';
            }
        }
        
        // Default to neutral if no clear sentiment found
        return 'neutral';
    }

    /**
     * Process call reply with AI analysis
     */
    public function processCallReply(Request $request)
    {
        $data = $request->validate([
            'call_id' => 'required|exists:call_status,id',
            'message' => 'required|string',
            'profile_id' => 'required|string',
            'sender' => 'required|string'
        ]);

        try {
            $call = CallStatus::findOrFail($data['call_id']);
            
            // Update conversation history
            $conversationHistory = $call->conversation_history ? json_decode($call->conversation_history, true) : [];
            $conversationHistory[] = [
                'sender' => $data['sender'],
                'message' => $data['message'],
                'timestamp' => now()->toISOString()
            ];
            
            // Analyze reply with AI using intelligent context analysis
            $chatGPT = new ChatGPT();
            
            // Use heredoc syntax for maximum reliability
            $originalMessageText = $call->original_message ?? 'No original message available';
            $analysisPrompt = <<<EOD
You are an expert LinkedIn conversation analyst. Your PRIMARY GOAL is to help schedule meetings with prospects. Analyze this message reply for call scheduling intent and lead qualification.

ORIGINAL CALL MESSAGE: {$originalMessageText}
REPLY MESSAGE: {$data['message']}

ANALYSIS INSTRUCTIONS:
Analyze the reply message by understanding the context, tone, and underlying intent. Look beyond simple keyword matching and focus on:

1. **Intent Analysis**: What is the person actually trying to communicate?
   - Are they expressing genuine interest in scheduling a call?
   - Are they politely declining or showing disinterest?
   - Are they asking for more information before deciding?
   - Are they suggesting alternative times or methods?
   - Are they being evasive or non-committal?

2. **Sentiment Analysis**: What is the emotional tone and attitude?
   - Positive: Enthusiastic, excited, eager, grateful
   - Neutral: Professional, matter-of-fact, cautious
   - Negative: Dismissive, frustrated, uninterested, annoyed

3. **Context Understanding**: Consider the full conversation context
   - How does this reply relate to the original call request?
   - Are there any subtle cues about their availability or interest level?
   - What might they be thinking or feeling based on their response?

4. **Meeting Scheduling Focus**: Always look for opportunities to suggest or schedule meetings
   - If they show any interest, immediately suggest a meeting
   - If they have concerns, address them and then suggest a meeting
   - If they're hesitant, offer a brief meeting to discuss their concerns
   - If they're busy, suggest a quick 15-minute call
   - If they're interested, suggest a longer meeting to discuss details
   - If they're asking questions, answer them and then suggest a meeting to discuss further

5. **Actionable Insights**: What should be the next step to move toward scheduling?
   - If interested: How can we move forward with scheduling?
   - If hesitant: What information might help them decide about a meeting?
   - If declining: Is there a way to maintain the relationship and suggest a future meeting?
   - If asking questions: What do they need to know before agreeing to a meeting?

REQUIRED OUTPUT (JSON format only - NO OTHER TEXT):
{
  "intent": "available|interested|not_interested|needs_more_info|reschedule_request|busy|greeting|scheduling_request",
  "sentiment": "positive|neutral|negative",
  "next_action": "schedule_call|send_calendar|send_info|follow_up_later|end_conversation|ask_availability",
  "suggested_response": "Natural, human-like response that focuses on scheduling a meeting. No placeholders, brackets, or generic text. Be specific and personal to the lead.",
  "lead_score": 1-10,
  "is_positive": true|false,
  "reasoning": "Brief explanation of your analysis"
}

CRITICAL: Return ONLY valid JSON. No explanations, no markdown, no additional text. The response must be parseable JSON.

Focus on understanding the human behind the message and always look for opportunities to schedule meetings.
EOD;

            $aiAnalysis = $chatGPT->generateContent($analysisPrompt);
            $analysis = json_decode($aiAnalysis['content'], true);
            
            // Log the raw AI response for debugging
            Log::info('🤖 AI Analysis Raw Response (processCallReply)', [
                'raw_content' => $aiAnalysis['content'],
                'json_decode_result' => $analysis,
                'json_last_error' => json_last_error_msg()
            ]);
            
            // Handle invalid JSON response
            if (!$analysis || json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('⚠️ AI Analysis failed or returned invalid JSON, using fallback (processCallReply)', [
                    'raw_content' => $aiAnalysis['content'],
                    'json_error' => json_last_error_msg()
                ]);
                
                $analysis = [
                    'intent' => 'unknown',
                    'sentiment' => 'neutral',
                    'lead_score' => 5,
                    'is_positive' => false,
                    'next_action' => 'follow_up_later',
                    'suggested_response' => 'Thank you for your response. I\'ll follow up with you soon.'
                ];
            } else {
                // Validate that we have the required fields
                $analysis = array_merge([
                    'intent' => 'unknown',
                    'sentiment' => 'neutral',
                    'lead_score' => 5,
                    'is_positive' => false,
                    'next_action' => 'follow_up_later',
                    'suggested_response' => 'Thank you for your response. I\'ll follow up with you soon.'
                ], $analysis);
                
                Log::info('✅ AI Analysis successful (processCallReply)', [
                    'intent' => $analysis['intent'],
                    'sentiment' => $analysis['sentiment'],
                    'lead_score' => $analysis['lead_score'],
                    'is_positive' => $analysis['is_positive']
                ]);
            }
            
            // Update call status based on AI analysis
            $newStatus = $this->determineCallStatus($analysis['intent'] ?? 'unknown');
            
            // Add AI response to conversation history
            $aiResponse = [
                'type' => 'ai_response',
                'message' => $analysis['suggested_response'] ?? 'Thank you for your response. I\'ll follow up with you soon.',
                'timestamp' => now()->toISOString(),
                'intent' => $analysis['intent'] ?? 'unknown',
                'sentiment' => $analysis['sentiment'] ?? 'neutral',
                'lead_score' => $analysis['lead_score'] ?? 5,
                'is_positive' => $analysis['is_positive'] ?? false,
                'next_action' => $analysis['next_action'] ?? 'follow_up_later',
                'reasoning' => $analysis['reasoning'] ?? ''
            ];
            
            $conversationHistory[] = $aiResponse;
            
            // Prepare update data
            $updateData = [
                'call_status' => $newStatus,
                'conversation_history' => json_encode($conversationHistory),
                'ai_analysis' => $analysis,
                'lead_category' => $this->categorizeLead($analysis),
                'lead_score' => $analysis['lead_score'] ?? 5,
                'last_interaction_at' => now(),
                'interaction_count' => $call->interaction_count + 1
            ];
            
            // If this is a positive response that should trigger calendar link, prepare for scheduling
            if (($analysis['is_positive'] ?? false) || 
                in_array($analysis['intent'] ?? '', ['available', 'interested', 'scheduling_request']) ||
                ($analysis['lead_score'] ?? 0) >= 7) {
                
                // Generate calendar link if not already present
                if (!$call->calendar_link) {
                    $calendarLink = $this->generateCalendarLink($call);
                    $updateData['calendar_link'] = $calendarLink;
                }
                
                // Update status to scheduling_initiated if it's a positive response
                if ($newStatus !== 'scheduling_initiated') {
                    $updateData['call_status'] = 'scheduling_initiated';
                }
            }
            
            $call->update($updateData);
            
            // Trigger appropriate action based on AI analysis
            $this->triggerAIAction($call, $analysis);
            
            // If scheduling was initiated, include details so the extension can act
            $schedulingDetails = null;
            if ($call->refresh()->call_status === 'scheduling_initiated') {
                $schedulingDetails = [
                    'calendar_link' => $call->calendar_link,
                    'scheduling_message' => $this->generateSchedulingMessage($call, $call->calendar_link)
                ];
            }
            
            return response()->json([
                'message' => 'Reply processed successfully',
                'analysis' => $analysis,
                'new_status' => $newStatus,
                'suggested_response' => $analysis['suggested_response'] ?? null,
                'scheduling' => $schedulingDetails
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed to process reply: ' . $th->getMessage()
            ], 422);
        }
    }

    /**
     * Determine call status based on AI analysis
     */
    private function determineCallStatus($intent)
    {
        return match($intent) {
            'interested' => 'interested',
            'available' => 'scheduling_initiated',
            'scheduling_request' => 'scheduling_initiated',
            'not_interested' => 'not_interested',
            'needs_more_info' => 'needs_info',
            'reschedule_request' => 'reschedule_request',
            'busy' => 'busy',
            default => 'replied'
        };
    }

    /**
     * Categorize lead based on AI analysis
     */
    private function categorizeLead($analysis)
    {
        $score = $analysis['lead_score'] ?? 5;
        $sentiment = $analysis['sentiment'] ?? 'neutral';
        
        if ($score >= 8 && $sentiment === 'positive') {
            return 'hot_lead';
        } elseif ($score >= 6) {
            return 'warm_lead';
        } elseif ($score >= 4) {
            return 'cold_lead';
        } else {
            return 'not_qualified';
        }
    }

    /**
     * Trigger AI-driven action based on analysis
     */
    private function triggerAIAction($call, $analysis)
    {
        $nextAction = $analysis['next_action'] ?? 'follow_up_later';
        
        switch($nextAction) {
            case 'schedule_call':
            case 'send_calendar':
                $this->initiateCallScheduling($call);
                break;
                
            case 'send_info':
                $this->sendAdditionalInfo($call);
                break;
                
            case 'follow_up_later':
                $this->scheduleFollowUp($call);
                break;
                
            case 'end_conversation':
                $this->endConversation($call);
                break;
        }
    }

    /**
     * Initiate call scheduling process
     */
    private function initiateCallScheduling($call)
    {
        // Generate calendar link (placeholder - integrate with actual calendar service)
        $calendarLink = $this->generateCalendarLink($call);
        
        // Generate AI-powered scheduling message with the generated calendar link
        $schedulingMessage = $this->generateSchedulingMessage($call, $calendarLink);
        
        // Update call status
        $call->update([
            'call_status' => 'scheduling_initiated',
            'calendar_link' => $calendarLink
        ]);
        
        // TODO: Send scheduling message via extension
        Log::info("Call scheduling initiated", [
            'call_id' => $call->id,
            'recipient' => $call->recipient,
            'calendar_link' => $calendarLink,
            'scheduling_message' => $schedulingMessage
        ]);
    }

    /**
     * Generate calendar link for scheduling
     */
    private function generateCalendarLink($call, $user = null)
    {
        return app(CalendarLinkService::class)->generateForCall($call, $user);
    }

    /**
     * Generate AI-powered scheduling message
     */
    private function generateSchedulingMessage($call, $calendarLink = null)
    {
        try {
            $chatGPT = new ChatGPT();
            
            $originalMessageText = $call->original_message ?? 'No original message available';
            $calendarLink = $calendarLink ?? $call->calendar_link ?? 'https://app.linkdominator.com/schedule-call/' . $call->id;
            
            Log::info('Generating scheduling message:', [
                'passed_calendar_link' => $calendarLink,
                'call_calendar_link' => $call->calendar_link,
                'final_calendar_link' => $calendarLink
            ]);
            
            $prompt = "Generate a simple follow-up message to schedule a call. This is a LinkedIn conversation follow-up, so keep it casual and direct. 

Requirements:
- Just ask them to select a convenient time using the calendar link
- Use the EXACT calendar link provided: {$calendarLink}
- Do NOT modify or shorten the calendar link
- No placeholders like [Your Name], [Your Company], etc.
- No signatures or formal closings
- Keep it under 30 words
- Be friendly and conversational

IMPORTANT: Use this exact link: {$calendarLink}";

            $result = $chatGPT->generateContent($prompt);
            $message = $result['content'] ?? '';
            
            // Ensure the correct calendar link is used in the message
            // Replace any calendar link in the message with the correct one
            $message = preg_replace('/https:\/\/calendly\.com\/[^\s]+/', $calendarLink, $message);
            
            return $message;
            
        } catch (\Throwable $th) {
            return "Hi {$call->recipient}, here's the link to schedule a call at your convenience: {$calendarLink}. Let me know if you have any questions!";
        }
    }

    /**
     * Send additional information to lead
     */
    private function sendAdditionalInfo($call)
    {
        // TODO: Implement sending additional information
        Log::info("Additional info requested for call", ['call_id' => $call->id]);
    }

    /**
     * Schedule follow-up message
     */
    private function scheduleFollowUp($call)
    {
        // TODO: Implement follow-up scheduling
        Log::info("Follow-up scheduled for call", ['call_id' => $call->id]);
    }

    /**
     * End conversation
     */
    private function endConversation($call)
    {
        $call->update(['call_status' => 'conversation_ended']);
        Log::info("Conversation ended for call", ['call_id' => $call->id]);
    }

    /**
     * Schedule a call (manual scheduling)
     */
    public function scheduleCall(Request $request)
    {
        $data = $request->validate([
            'call_id' => 'required|exists:call_status,id',
            'scheduled_time' => 'required|date|after:now',
            'timezone' => 'required|string',
            'meeting_link' => 'nullable|url'
        ]);

        $call = CallStatus::findOrFail($data['call_id']);
        
        $call->update([
            'scheduled_time' => $data['scheduled_time'],
            'timezone' => $data['timezone'],
            'meeting_link' => $data['meeting_link'],
            'call_status' => 'scheduled'
        ]);

        return response()->json([
            'message' => 'Call scheduled successfully',
            'call' => $call
        ]);
    }

    /**
     * Get messages ready to send based on AI mode settings
     */
    public function getMessagesReadyToSend(Request $request)
    {
        try {
            $this->checkAuthorization($request);
        } catch (\Throwable $th) {
            return response()->json([
                "message" => $th->getMessage(),
                "status" => 400
            ], 400);
        }

        $user = User::where('linkedin_id', $request->header('lk-id'))->first();
        
        if (!$user) {
            return response()->json([
                'error' => 'User not found'
            ], 401);
        }

        // Get messages that are ready to send
        $readyMessages = CallStatus::where('user_id', $user->id)
            ->readyToSend()
            ->get();

        return response()->json([
            'messages' => $readyMessages,
            'count' => $readyMessages->count()
        ]);
    }

    /**
     * Update message status after sending
     */
    public function updateMessageStatus(Request $request, $id)
    {
        try {
            $this->checkAuthorization($request);
        } catch (\Throwable $th) {
            return response()->json([
                "message" => $th->getMessage(),
                "status" => 400
            ], 400);
        }

        $user = User::where('linkedin_id', $request->header('lk-id'))->first();
        
        if (!$user) {
            return response()->json([
                'error' => 'User not found'
            ], 401);
        }

        $call = CallStatus::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$call) {
            return response()->json([
                'error' => 'Call not found'
            ], 404);
        }

        $status = $request->input('status', 'sent');
        $aiResponse = $request->input('ai_response');
        $analysis = $request->input('analysis');
        $scheduledSendAt = $request->input('scheduled_send_at');
        $pendingMessage = $request->input('pending_message');
        $sentMessage = $request->input('sent_message'); // The actual message that was sent
        
        $updateData = ['call_status' => $status];
        
        // If storing for review, save the AI response and scheduled time
        if ($status === 'pending_review' && $aiResponse) {
            $updateData['pending_message'] = $aiResponse;
            if ($analysis) {
                $updateData['ai_analysis'] = $analysis;
            }
            if ($scheduledSendAt) {
                $updateData['scheduled_send_at'] = $scheduledSendAt;
            }
        }
        
        // If message was sent, clear pending message and scheduled time, and update call_status
        if ($status === 'response_sent') {
            $updateData['pending_message'] = null;
            $updateData['scheduled_send_at'] = null;
            // Update call_status to indicate message was sent
            $updateData['call_status'] = 'response_sent';
            
            // Add the sent message to conversation history
            if ($sentMessage) {
                $conversationHistory = json_decode($call->conversation_history ?? '[]', true) ?: [];
                $conversationHistory[] = [
                    'type' => 'ai',
                    'message' => $sentMessage,
                    'timestamp' => now()->toISOString()
                ];
                $updateData['conversation_history'] = json_encode($conversationHistory);
                $updateData['last_interaction_at'] = now();
                $updateData['interaction_count'] = $call->interaction_count + 1;
            }
        }
        
        // If explicitly setting pending_message to null, clear it
        if ($pendingMessage === null) {
            $updateData['pending_message'] = null;
        }
        
        // If explicitly setting scheduled_send_at to null, clear it
        if ($scheduledSendAt === null) {
            $updateData['scheduled_send_at'] = null;
        }
        
        $call->update($updateData);

        return response()->json([
            'message' => 'Message status updated successfully',
            'status' => $status
        ]);
    }

    /**
     * Update pending message for review mode
     */
    public function updatePendingMessage(Request $request, $id)
    {
        try {
            $this->checkAuthorization($request);
        } catch (\Throwable $th) {
            return response()->json([
                "message" => $th->getMessage(),
                "status" => 400
            ], 400);
        }

        $user = User::where('linkedin_id', $request->header('lk-id'))->first();
        
        if (!$user) {
            return response()->json([
                'error' => 'User not found'
            ], 401);
        }

        $call = CallStatus::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$call) {
            return response()->json([
                'error' => 'Call not found'
            ], 404);
        }

        $pendingMessage = $request->input('pending_message');
        $scheduledSendAt = $request->input('scheduled_send_at');
        $analysis = $request->input('analysis');
        
        $updateData = [
            'call_status' => 'pending_review',
            'pending_message' => $pendingMessage
        ];
        
        if ($scheduledSendAt) {
            $updateData['scheduled_send_at'] = $scheduledSendAt;
        }
        
        if ($analysis) {
            $updateData['ai_analysis'] = $analysis;
        }
        
        // Log what we're about to update
        Log::info('🔍 updatePendingMessage: About to update fields', [
            'call_id' => $call->id,
            'fields_to_update' => array_keys($updateData),
            'original_message_before' => $call->original_message,
            'pending_message_being_set' => $pendingMessage
        ]);

        $call->update($updateData);

        // Log what actually changed
        $call->refresh();
        Log::info('🔍 updatePendingMessage: After update', [
            'call_id' => $call->id,
            'original_message_after' => $call->original_message,
            'pending_message_after' => $call->pending_message
        ]);

        return response()->json([
            'message' => 'Pending message updated successfully',
            'scheduled_send_at' => $scheduledSendAt
        ]);
    }

    /**
     * Get call status including AI mode settings
     */
    public function getCallStatus(Request $request, $id)
    {
        try {
            $this->checkAuthorization($request);
        } catch (\Throwable $th) {
            return response()->json([
                "message" => $th->getMessage(),
                "status" => 400
            ], 400);
        }

        $user = User::where('linkedin_id', $request->header('lk-id'))->first();
        
        if (!$user) {
            return response()->json([
                'error' => 'User not found'
            ], 401);
        }

        $call = CallStatus::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$call) {
            return response()->json([
                'error' => 'Call not found'
            ], 404);
        }

        return response()->json([
            'call_id' => $call->id,
            'recipient' => $call->recipient,
            'call_status' => $call->call_status ?? 'pending_review',
            'scheduled_send_at' => $call->scheduled_send_at,
            'original_message' => $call->original_message,
            'pending_message' => $call->pending_message,
            'ai_analysis' => $call->ai_analysis
        ]);
    }

    /**
     * Get AI-generated message for a call
     */
    public function getCallMessage(Request $request, $id)
    {
        // Get user from lk-id header (same as storeCallStatus)
        $user = User::where('linkedin_id', $request->header('lk-id'))->first();
        
        if (!$user) {
            return response()->json([
                'error' => 'User not found'
            ], 401);
        }

        $call = CallStatus::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$call) {
            return response()->json([
                'error' => 'Call not found'
            ], 404);
        }

        $originalMessage = $call->original_message ?? 'No AI message generated yet';

        $response = [
            'message' => $originalMessage,
            'call_id' => $call->id,
            'recipient' => $call->recipient,
            'original_message' => $originalMessage,
            'call_status' => [
                'call_status' => $call->call_status ?? 'pending_review',
                'scheduled_send_at' => $call->scheduled_send_at
            ]
        ];
        
        Log::info('🔍 getCallMessage response', [
            'call_id' => $call->id,
            'recipient' => $call->recipient,
            'original_message' => $originalMessage,
            'response' => $response
        ]);
        
        return response()->json($response);
    }

    /**
     * Fetch scheduling info for a call (calendar link + suggested message)
     */
    public function getSchedulingInfo($id)
    {
        $call = CallStatus::findOrFail($id);

        return response()->json([
            'call_id' => $call->id,
            'status' => $call->call_status,
            'calendar_link' => $call->calendar_link,
            'scheduling_message' => $this->generateSchedulingMessage($call, $call->calendar_link)
        ]);
    }

    /**
     * Test reminder system
     */
    public function testReminderSystem()
    {
        try {
            // Run the reminder scheduler
            Artisan::call('calls:send-reminders');
            $output = Artisan::output();
            
            return response()->json([
                'message' => 'Reminder system test completed successfully',
                'output' => $output
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Reminder system test failed: ' . $th->getMessage()
            ], 422);
        }
    }

    /**
     * Get pending reminders for extension to process
     */
    public function getPendingReminders(Request $request)
    {
        try {
            $this->checkAuthorization($request);
        } catch (\Throwable $th) {
            return response()->json([
                "message" => $th->getMessage(),
                "status" => 400
            ], 400);
        }

        $user = User::where('linkedin_id', $request->header('lk-id'))->first();
        
        if (!$user) {
            return response()->json([
                'error' => 'User not found'
            ], 401);
        }

        // Get pending reminders for this user's LinkedIn ID
        $pendingReminders = \DB::table('reminder_queue')
            ->where('linkedin_id', $user->linkedin_id)
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'reminders' => $pendingReminders
        ]);
    }

    /**
     * Update reminder status after extension processes it
     */
    public function updateReminderStatus(Request $request)
    {
        try {
            $this->checkAuthorization($request);
        } catch (\Throwable $th) {
            return response()->json([
                "message" => $th->getMessage(),
                "status" => 400
            ], 400);
        }

        $user = User::where('linkedin_id', $request->header('lk-id'))->first();
        
        if (!$user) {
            return response()->json([
                'error' => 'User not found'
            ], 401);
        }

        $reminderId = $request->input('reminder_id');
        $status = $request->input('status'); // 'processing', 'sent', 'failed'
        $errorMessage = $request->input('error_message');

        if (!in_array($status, ['processing', 'sent', 'failed'])) {
            return response()->json([
                'error' => 'Invalid status'
            ], 400);
        }

        $updateData = [
            'status' => $status,
            'updated_at' => now()
        ];

        if ($status === 'sent') {
            $updateData['processed_at'] = now();
        }

        if ($errorMessage) {
            $updateData['error_message'] = $errorMessage;
        }

        $updated = \DB::table('reminder_queue')
            ->where('id', $reminderId)
            ->where('linkedin_id', $user->linkedin_id)
            ->update($updateData);

        if ($updated) {
            return response()->json([
                'success' => true,
                'message' => 'Reminder status updated successfully'
            ]);
        } else {
            return response()->json([
                'error' => 'Reminder not found or update failed'
            ], 404);
        }
    }

    /**
     * Check for call responses and return analysis
     */
    public function checkCallResponse(Request $request, $callId)
    {
        try {
            $call = CallStatus::where('id', $callId)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            // Check if there's a recent response (within last 24 hours)
            $hasRecentResponse = false;
            $responseData = null;

            if ($call->conversation_history) {
                $conversationHistory = json_decode($call->conversation_history, true);
                $lastMessage = end($conversationHistory);
                
                if ($lastMessage && $lastMessage['sender'] === 'lead') {
                    $lastResponseTime = \Carbon\Carbon::parse($lastMessage['timestamp']);
                    $hasRecentResponse = $lastResponseTime->isAfter(now()->subDay());
                    
                    if ($hasRecentResponse) {
                        $responseData = [
                            'hasResponse' => true,
                            'message' => $lastMessage['message'],
                            'timestamp' => $lastMessage['timestamp'],
                            'ai_analysis' => $call->ai_analysis,
                            'call_status' => $call->call_status,
                            'lead_score' => $call->lead_score,
                            'lead_category' => $call->lead_category,
                            'isPositive' => $this->isPositiveResponse($call->ai_analysis),
                            'needsAction' => $this->needsAction($call->call_status),
                            'scheduling_ready' => $call->call_status === 'scheduling_initiated'
                        ];
                    }
                }
            }

            return response()->json([
                'hasResponse' => $hasRecentResponse,
                'call_id' => $callId,
                'call_status' => $call->call_status,
                'last_interaction' => $call->last_interaction_at,
                'response_data' => $responseData
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'hasResponse' => false,
                'error' => 'Failed to check response: ' . $th->getMessage()
            ], 422);
        }
    }

    /**
     * Store conversation message in call_status table
     */
    public function storeConversationMessage(Request $request)
    {
        $data = $request->validate([
            'call_id' => 'required|string',
            'message' => 'required|string',
            'sender' => 'required|string|in:lead,ai,user',
            'message_type' => 'nullable|string',
            'ai_analysis' => 'nullable|array',
            'lead_name' => 'nullable|string',
            'connection_id' => 'nullable|string',
            'conversation_urn_id' => 'nullable|string'
        ]);

        // Ensure ai_analysis is always set, even if null
        $data['ai_analysis'] = $data['ai_analysis'] ?? null;

        try {
            // Find the call record - MUST already exist from initial creation
            Log::info('🔍 Looking for call record with:', [
                'call_id' => $data['call_id'],
                'connection_id' => $data['connection_id'],
                'conversation_urn_id' => $data['conversation_urn_id']
            ]);

            $call = CallStatus::where('id', $data['call_id'])
                ->orWhere('connection_id', $data['call_id'])
                ->orWhere(function ($q) use ($data) {
                    if (!empty($data['connection_id'])) {
                        $q->where('connection_id', $data['connection_id']);
                    }
                })
                ->orWhere(function ($q) use ($data) {
                    if (!empty($data['conversation_urn_id'])) {
                        $q->where('conversation_urn_id', $data['conversation_urn_id']);
                    }
                })
                ->first();

            if (!$call) {
                // Do NOT create fallback records anymore; ensure single-row per conversation
                Log::warning('⚠️ Call record not found for storeConversationMessage', [
                    'call_id' => $data['call_id'],
                    'connection_id' => $data['connection_id'],
                    'conversation_urn_id' => $data['conversation_urn_id'],
                    'lead_name' => $data['lead_name']
                ]);

                // Let's check what records actually exist
                $existingRecords = CallStatus::where('connection_id', $data['connection_id'])
                    ->orWhere('conversation_urn_id', $data['conversation_urn_id'])
                    ->get(['id', 'connection_id', 'conversation_urn_id', 'recipient']);
                
                Log::info('🔍 Existing records found:', $existingRecords->toArray());

                return response()->json([
                    'message' => 'Call record not found. Please create the call via the initial endpoint and reuse its id.',
                    'error' => 'CALL_NOT_FOUND'
                ], 404);
            }

            Log::info('✅ Found call record:', [
                'id' => $call->id,
                'connection_id' => $call->connection_id,
                'conversation_urn_id' => $call->conversation_urn_id,
                'recipient' => $call->recipient
            ]);

            // Get existing conversation history
            $conversationHistory = json_decode($call->conversation_history ?? '[]', true) ?: [];

            // Create message entry
            $messageEntry = [
                'type' => $data['sender'],
                'message' => $data['message'],
                'timestamp' => now()->toISOString(),
                'message_type' => $data['message_type'] ?? 'text'
            ];

            // Add AI analysis if provided
            if (isset($data['ai_analysis']) && $data['ai_analysis'] && $data['sender'] === 'ai') {
                $messageEntry['ai_analysis'] = $data['ai_analysis'];
                $messageEntry['intent'] = $data['ai_analysis']['intent'] ?? 'unknown';
                $messageEntry['sentiment'] = $data['ai_analysis']['sentiment'] ?? 'neutral';
                $messageEntry['lead_score'] = $data['ai_analysis']['lead_score'] ?? 5;
                $messageEntry['is_positive'] = $data['ai_analysis']['is_positive'] ?? false;
            }

            // Add to conversation history
            $conversationHistory[] = $messageEntry;

            // Update call record
            $updateData = [
                'conversation_history' => json_encode($conversationHistory),
                'last_interaction_at' => now(),
                'interaction_count' => $call->interaction_count + 1
            ];

            // Update AI analysis and lead scoring if this is an AI response
            if ($data['sender'] === 'ai' && isset($data['ai_analysis']) && $data['ai_analysis']) {
                $analysis = $data['ai_analysis'];
                $updateData['ai_analysis'] = $analysis;
                $updateData['lead_category'] = $this->categorizeLead($analysis);
                $updateData['lead_score'] = $analysis['lead_score'] ?? 5;

                // Update call status based on analysis
                if (isset($analysis['intent'])) {
                    $updateData['call_status'] = $this->determineCallStatus($analysis['intent']);
                }

                // Generate calendar link for positive responses
                if (($analysis['is_positive'] ?? false) || 
                    in_array($analysis['intent'] ?? '', ['available', 'interested', 'scheduling_request']) ||
                    ($analysis['lead_score'] ?? 0) >= 7) {

                    if (!$call->calendar_link) {
                        $updateData['calendar_link'] = $this->generateCalendarLink($call);
                    }

                    if (($updateData['call_status'] ?? null) !== 'scheduling_initiated') {
                        $updateData['call_status'] = 'scheduling_initiated';
                    }
                }
            }

            $call->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Conversation message stored successfully',
                'call_id' => $call->id,
            ]);
        } catch (\Throwable $th) {
            Log::error('Failed to store conversation message: '.$th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to store conversation message',
                'error' => $th->getMessage()
            ], 422);
        }
    }

    /**
     * Get conversation history for a call
     */
    public function getConversationHistory(Request $request, $callId)
    {
        try {
            $call = CallStatus::where('id', $callId)
                ->orWhere('connection_id', $callId)
                ->firstOrFail();

            $conversationHistory = json_decode($call->conversation_history ?? '[]', true) ?: [];

            // Prepend the original message as the first message in the conversation
            if ($call->original_message) {
                $originalMessageEntry = [
                    'type' => 'ai',
                    'message' => $call->original_message,
                    'timestamp' => $call->created_at ? $call->created_at->toISOString() : now()->toISOString(),
                    'message_type' => 'original_message'
                ];
                
                // Insert at the beginning of the array
                array_unshift($conversationHistory, $originalMessageEntry);
            }

            return response()->json([
                'success' => true,
                'call_id' => $call->id,
                'recipient' => $call->recipient,
                'call_status' => $call->call_status,
                'lead_category' => $call->lead_category,
                'lead_score' => $call->lead_score,
                'calendar_link' => $call->calendar_link,
                'conversation_history' => $conversationHistory,
                'total_messages' => count($conversationHistory),
                'last_interaction' => $call->last_interaction_at
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve conversation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate calendar link for positive responses
     */
    public function generateCalendarLinkForCall(Request $request, $callId)
    {
        try {
            // Get user from lk-id header (extension authentication)
            $user = User::where('linkedin_id', $request->header('lk-id'))->first();
            
            if (!$user) {
                return response()->json(['error' => 'User not found'], 401);
            }
            
            // Check if this is a temporary ID (contains underscore and timestamp)
            if (strpos($callId, '_') !== false && is_numeric(substr($callId, strrpos($callId, '_') + 1))) {
                // This is a temporary ID, generate a simple calendar link
                $calendarLink = $this->generateSimpleCalendarLink();
                $schedulingMessage = $this->generateSimpleSchedulingMessage();
                
                return response()->json([
                    'calendar_link' => $calendarLink,
                    'scheduling_message' => $schedulingMessage,
                    'call_status' => 'scheduling_initiated'
                ]);
            }
            
            // Original logic for database call IDs
            // Find call record by ID only (not by user_id) since call records might be created without proper user association
            $call = CallStatus::where('id', $callId)->first();
            
            if (!$call) {
                return response()->json(['error' => 'Call not found'], 404);
            }

            // Generate calendar link with authenticated user
            $calendarLink = $this->generateCalendarLink($call, $user);
            
            // Generate AI scheduling message with the generated calendar link
            $schedulingMessage = $this->generateSchedulingMessage($call, $calendarLink);
            
            // Update call status and store call ID for webhook matching
            $call->update([
                'call_status' => 'scheduling_initiated',
                'calendar_link' => $calendarLink,
                'calendly_event_id' => "pending_{$call->id}" // Store call ID for webhook matching
            ]);

            return response()->json([
                'calendar_link' => $calendarLink,
                'scheduling_message' => $schedulingMessage,
                'call_status' => 'scheduling_initiated'
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to generate calendar link: ' . $th->getMessage()
            ], 422);
        }
    }

    /**
     * Generate simple calendar link for temporary call IDs
     */
    private function generateSimpleCalendarLink()
    {
        return app(CalendarLinkService::class)->generateSimple();
    }

    /**
     * Generate simple scheduling message for temporary call IDs
     */
    private function generateSimpleSchedulingMessage()
    {
        try {
            $chatGPT = new ChatGPT();
            
            $prompt = "Generate a simple LinkedIn follow-up message to schedule a call. Keep it casual and direct - just ask them to select a convenient time using the calendar link. No placeholders, no signatures, no formal closings. Under 30 words, friendly and conversational.";
            
            $response = $chatGPT->generateContent($prompt);
            
            return $response['content'] ?? "Hi! Here's the link to schedule a call at your convenience: [CALENDAR_LINK]. Let me know if you have any questions!";
            
        } catch (\Exception $e) {
            Log::error('Failed to generate simple scheduling message: ' . $e->getMessage());
            return "Hi! Here's the link to schedule a call at your convenience: [CALENDAR_LINK]. Let me know if you have any questions!";
        }
    }

    /**
     * Update pending message from web interface
     */
    public function updatePendingMessageWeb(Request $request)
    {
        $data = $request->validate([
            'call_id' => ['required'],
            'pending_message' => ['required']
        ]);

        $call = CallStatus::where('id', $data['call_id'])
            ->where('user_id', Auth::id())
            ->first();

        if (!$call) {
            notify()->error('Call not found');
            return redirect()->route('calls');
        }

        $updateData = [
            'pending_message' => $data['pending_message']
        ];

        $call->update($updateData);

        notify()->success('Pending message updated successfully');
        return redirect()->route('calls');
    }

    /**
     * Show call details page
     */
    public function showCallDetails($id)
    {
        try {
            $call = CallStatus::findOrFail($id);
            
            // Parse conversation history
            $conversationHistory = json_decode($call->conversation_history ?? '[]', true) ?: [];
            
            // Prepend the original message as the first message in the conversation
            if ($call->original_message) {
                $originalMessageEntry = [
                    'type' => 'ai',
                    'message' => $call->original_message,
                    'timestamp' => $call->created_at ? $call->created_at->toISOString() : now()->toISOString(),
                    'message_type' => 'original_message'
                ];
                
                // Insert at the beginning of the array
                array_unshift($conversationHistory, $originalMessageEntry);
            }
            
            // Parse AI analysis
            $aiAnalysis = null;
            if ($call->ai_analysis) {
                $aiAnalysis = is_string($call->ai_analysis) ? json_decode($call->ai_analysis, true) : $call->ai_analysis;
            }
            
            return view('calls.show', compact('call', 'conversationHistory', 'aiAnalysis'));
            
        } catch (\Exception $e) {
            return redirect()->route('calls')->with('error', 'Call not found');
        }
    }


    /**
     * Determine if response is positive based on AI analysis
     */
    private function isPositiveResponse($aiAnalysis)
    {
        if (!$aiAnalysis) return false;
        
        $analysis = is_string($aiAnalysis) ? json_decode($aiAnalysis, true) : $aiAnalysis;
        
        $intent = $analysis['intent'] ?? '';
        $sentiment = $analysis['sentiment'] ?? '';
        $leadScore = $analysis['lead_score'] ?? 0;
        
        return in_array($intent, ['interested', 'available', 'scheduling_request', 'reschedule_request']) && 
               $sentiment === 'positive' && 
               $leadScore >= 7;
    }

    /**
     * Check if call needs action based on status
     */
    private function needsAction($callStatus)
    {
        return in_array($callStatus, ['interested', 'needs_info', 'replied']);
    }

    /**
     * Manually trigger AI message generation for existing calls
     */
    public function triggerAIMessages()
    {
        try {
            $userId = Auth::id();
            $calls = CallStatus::where('user_id', $userId)
                ->whereNull('original_message')
                ->get();

            $generated = 0;
            foreach ($calls as $call) {
                try {
                    $chatGPT = new ChatGPT();
                    $aiResult = $chatGPT->generateCallMessage(
                        $call->recipient,
                        $call->company ?? null,
                        $call->industry ?? null
                    );
                    
                    $call->update([
                        'original_message' => $aiResult['content'],
                        'lead_score' => rand(6, 9), // Random score for testing
                        'lead_category' => rand(6, 9) >= 8 ? 'hot_lead' : 'warm_lead'
                    ]);
                    
                    $generated++;
                } catch (\Throwable $th) {
                    // Skip this call if AI generation fails
                    continue;
                }
            }
            
            return response()->json([
                'message' => "Successfully generated AI messages for {$generated} calls",
                'generated' => $generated,
                'total' => $calls->count()
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed to trigger AI messages: ' . $th->getMessage()
            ], 422);
        }
    }

    /**
     * Search for call record by connection_id
     */
    public function searchByConnection($connectionId)
    {
        try {
            $callStatus = CallStatus::where('connection_id', $connectionId)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$callStatus) {
                return response()->json([
                    'message' => 'No call record found for this connection_id',
                    'call_id' => null
                ], 404);
            }

            return response()->json([
                'message' => 'Call record found',
                'call_id' => $callStatus->id,
                'call_status' => $callStatus->call_status,
                'lead_name' => $callStatus->recipient,
                'created_at' => $callStatus->created_at
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed to search call record: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * Check if a call record already exists for a connection
     */
    public function checkExistingCall(Request $request)
    {
        $connectionId = $request->query('connection_id');
        
        if (!$connectionId) {
            return response()->json([
                'exists' => false,
                'message' => 'Connection ID is required'
            ], 400);
        }

        try {
            $call = CallStatus::where('connection_id', $connectionId)
                ->where('user_id', Auth::id())
                ->first();

            if ($call) {
                return response()->json([
                    'exists' => true,
                    'call_id' => $call->id,
                    'message' => 'Call record found'
                ]);
            } else {
                return response()->json([
                    'exists' => false,
                    'message' => 'No call record found'
                ]);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'exists' => false,
                'message' => 'Failed to check existing call: ' . $th->getMessage()
            ], 500);
        }
    }
}
