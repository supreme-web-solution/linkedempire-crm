<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CallManagerController;
use App\Http\Controllers\CallCampaignController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\AiwriterController;
use App\Http\Controllers\ChromeApiController;
use App\Http\Controllers\ContentCreatorController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Routes that don't require authentication (for chrome extension access)
Route::post('calls/analyze-message', [CallManagerController::class, 'analyzeMessageReply']);
Route::post('calls/conversation/store', [CallManagerController::class, 'storeConversationMessage']);
Route::get('calls/{id}/status', [CallManagerController::class, 'getCallStatus']);

// Calendly webhook (no authentication required)
Route::post('calendly/webhook', [App\Http\Controllers\CalendlyWebhookController::class, 'handle']);

// Chrome extension routes (require lk-id header validation)
Route::middleware(['api'])->group(function() {
    Route::get('campaigns', [CampaignController::class, 'campaign']); // tested
    Route::get('campaigns/status-updates', [CampaignController::class, 'getCampaignStatusUpdates']); // real-time updates
    Route::get('campaigns/{id}/debug-accept-rate', [CampaignController::class, 'debugAcceptRate']); // debug accept rate
    Route::get('campaign/{id}/leads', [CampaignController::class, 'campaignLeads']); // tested
    Route::get('campaign/{id}/sequence', [CampaignController::class, 'campaignSequence']); // tested
    Route::post('campaign/{id}/update', [CampaignController::class, 'campaignUpdate']);
    Route::post('campaign/{id}/update-node', [CampaignController::class, 'campaignSequenceUpdate']);
    Route::post('campaign/{id}/leadgen/store', [CampaignController::class, 'createLeadGenRunning']);
    Route::post('campaign/{campaignId}/leadgen/{leadId}/update', [CampaignController::class, 'updateLeadGenRunning']);
    Route::get('campaign/{campaignId}/leadgen', [CampaignController::class, 'getLeadGenRunning']); // tested
    Route::get('campaign/{campaignId}/leadgen/tracking', [CampaignController::class, 'getLeadGenTracking']); // new endpoint for tracking data
    Route::post('lead/{leadId}/update', [CampaignController::class, 'updateLeadNetworkDegree']);

    Route::post('book-call/store', [CallManagerController::class, 'storeCallStatus']);
    Route::post('calls/generate-message', [CallManagerController::class, 'generateCallMessage']);
    Route::post('calls/process-reply', [CallManagerController::class, 'processCallReply']);
    Route::post('calls/schedule', [CallManagerController::class, 'scheduleCall']);
    Route::get('calls/{id}/message', [CallManagerController::class, 'getCallMessage']);
    Route::get('calls/{id}/scheduling', [CallManagerController::class, 'getSchedulingInfo']);
    Route::get('calls/{id}/response', [CallManagerController::class, 'checkCallResponse']);
    Route::post('calls/{id}/calendar-link', [CallManagerController::class, 'generateCalendarLinkForCall']);
    Route::post('calls/test-reminders', [CallManagerController::class, 'testReminderSystem']);
    Route::get('reminders/pending', [CallManagerController::class, 'getPendingReminders']);
    Route::post('reminders/update-status', [CallManagerController::class, 'updateReminderStatus']);
    Route::post('calls/trigger-ai-messages', [CallManagerController::class, 'triggerAIMessages']);
    Route::get('calls/{id}/conversation', [CallManagerController::class, 'getConversationHistory']);
    Route::get('calls/search-by-connection/{connectionId}', [CallManagerController::class, 'searchByConnection']);
    Route::get('calls/ready-to-send', [CallManagerController::class, 'getMessagesReadyToSend']);
    Route::post('calls/{id}/update-status', [CallManagerController::class, 'updateMessageStatus']);
    Route::post('calls/{id}/pending-message', [CallManagerController::class, 'updatePendingMessage']);

    Route::get('call-campaigns/ready-to-send', [CallCampaignController::class, 'readyToSend']);
    Route::post('call-campaigns/messages/{id}/status', [CallCampaignController::class, 'updateMessageStatus']);
    
    // Content Creator API routes for Chrome extension
    Route::get('content-creator/scheduled-posts', [ContentCreatorController::class, 'getScheduledPosts']);
    Route::post('content-creator/posts/{id}/update-status', [ContentCreatorController::class, 'updatePostStatus']);
    
    // Inspiration Library API routes for Chrome extension
    Route::post('inspiration/save-viral-post', [App\Http\Controllers\InspirationController::class, 'store']);
});

Route::controller(LeadController::class)->group(function (){
    Route::get('leads/export', 'export')->name('api.leads.export');
    Route::get('leads/export/bulk', 'bulk_export')->name('api.leads.bulk_export');
});

Route::get('aicontents', [AiwriterController::class, 'aicontents']); 

Route::controller(ChromeApiController::class)->group(function (){
    // Regular routes
    Route::get('accessCheck', 'accessCheck');
    Route::get('audience', 'getAudience');
    Route::post('audience', 'storeAudience');
    Route::delete('audience', 'deleteAudience');
    Route::get('audience/list', 'getAudienceList');
    Route::post('audience/list', 'storeAudienceList');
    Route::put('audience/list', 'updateAudienceList');
    Route::delete('audience/list', 'deleteAudienceList');
    Route::get('audience/list/export', 'audienceListExport');
    Route::post('audiences/export/{audience_id}', 'export');
    Route::get('esp/config', 'getEspConfig');
    Route::get('audience/count', 'audienceRecent');
    Route::get('autoresponses', 'getAutoResponses');
    Route::post('autoresponse/store', 'storeAutoResponse');
    Route::get('autoresponse/show/{id}', 'showAutoResponse');
    Route::put('autoresponse/update/{id}', 'updateAutoResponse');
    Route::delete('autoresponse/delete/{id}', 'deleteAutoResponse');
    Route::get('lang', 'langFilter');
    Route::post('conf', 'LinkedInConfig');
    Route::post('audience/post-likers', 'fetchPostLikersFromPhantom');
    Route::post('audience/post-comments', 'fetchPostCommentsFromPhantom');
    Route::post('audience/search-export', 'fetchSearchResultsFromPhantom');
    Route::post('snleads/store', 'storeSnLeads');
    Route::get('snleads/lists', 'getSnLeadList');
    Route::post('snleads/list/store', 'storeSnLeadList');
    Route::post('activites', 'storeUserActivity');
    
    // Call management routes
    Route::get('calls/check-existing', 'checkExistingCall');
    
    // Post comment generation
    Route::post('post/generate-comment', 'generatePostComment');
    
    // LinkedIn ID sync endpoint
    Route::post('auth/sync-linkedin-id', 'syncLinkedInId');
}); 

// V2 API isolation layer
Route::prefix('v2')->middleware(['api'])->group(function () {
    require base_path('routes/api_v2.php');
});
