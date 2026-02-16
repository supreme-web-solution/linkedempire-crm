<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Middleware\VerifyCsrfToken;

use App\Http\Controllers\Authenticate\LoginController;
use App\Http\Controllers\Authenticate\ForgotPasswordController;
use App\Http\Controllers\Authenticate\ProfileController;
use App\Http\Controllers\Authenticate\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CallManagerController;
use App\Http\Controllers\CalendlyController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\AiwriterController;
use App\Http\Controllers\SchedulePostController;
use App\Http\Controllers\SocialAccountController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamInviteController;
use App\Http\Controllers\UserManagerController;
use App\Http\Controllers\JvzooIpnController;
use App\Http\Controllers\CommentFeedController;
use App\Http\Controllers\ContentCreatorController;
use App\Http\Controllers\JVZooWebhookController;

Route::get('/', function () {
    return redirect()->route('auth.login');
});

Route::post('/ipn/jvzoo', [JvzooIpnController::class, 'JVZoo'])->name('ipn.jvzoo');

Route::match(['get', 'post'], '/debug/rapidapi/proxy', function (Request $request) {
    logger('RapidAPI debug route accessed');
    $allowedEnvironments = ['local', 'development', 'staging'];

    if (!app()->environment($allowedEnvironments)) {
        abort(403, 'RapidAPI debug route is disabled in this environment.');
    }

    $validated = $request->validate([
        'host' => 'required|string',
        'path' => 'required|string',
        'method' => 'nullable|string|in:GET,POST,PUT,PATCH,DELETE',
        'query' => 'nullable|string',
        'payload' => 'nullable|string',
        'headers' => 'nullable|string',
    ]);

    $allowedHosts = config('services.rapidapi.allowed_hosts', []);

    if (!in_array($validated['host'], $allowedHosts, true)) {
        return response()->json([
            'error' => 'Host not allowed for RapidAPI testing.',
            'allowed_hosts' => $allowedHosts,
        ], 422);
    }

    $method = strtoupper($validated['method'] ?? 'GET');

    $queryParams = [];
    if (!empty($validated['query'])) {
        $queryParams = json_decode($validated['query'], true);
        if (!is_array($queryParams)) {
            return response()->json(['error' => 'Invalid query JSON payload.'], 422);
        }
    }

    $payload = [];
    if (!empty($validated['payload'])) {
        $payload = json_decode($validated['payload'], true);
        if (!is_array($payload)) {
            return response()->json(['error' => 'Invalid payload JSON body.'], 422);
        }
    }

    $extraHeaders = [];
    if (!empty($validated['headers'])) {
        $extraHeaders = json_decode($validated['headers'], true);
        if (!is_array($extraHeaders)) {
            return response()->json(['error' => 'Invalid headers JSON payload.'], 422);
        }
    }

    $rapidApiKey = config('services.rapidapi.key');

    if (empty($rapidApiKey)) {
        return response()->json(['error' => 'RAPIDAPI_KEY is not configured.'], 500);
    }

    $client = Http::timeout(45)->withHeaders(array_merge([
        'X-RapidAPI-Key' => $rapidApiKey,
        'X-RapidAPI-Host' => $validated['host'],
        'Accept' => 'application/json',
    ], $extraHeaders));

    if (!empty($queryParams)) {
        $client = $client->withOptions(['query' => $queryParams]);
    }

    $url = 'https://' . $validated['host'] . '/' . ltrim($validated['path'], '/');

    $response = match ($method) {
        'POST' => $client->post($url, $payload),
        'PUT' => $client->put($url, $payload),
        'PATCH' => $client->patch($url, $payload),
        'DELETE' => $client->delete($url, $payload),
        default => $client->get($url),
    };

    $body = $response->json();
    if (is_null($body)) {
        $decoded = json_decode($response->body(), true);
        $body = $decoded ?? $response->body();
    }

    return response()->json([
        'requested_url' => $url,
        'method' => $method,
        'status' => $response->status(),
        'response_headers' => $response->headers(),
        'body' => $body,
    ], $response->status());
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])->name('debug.rapidapi.proxy');

Route::controller(LoginController::class)->group(function () {
    Route::get('/auth/signin', 'index')->name('auth.login');
    Route::post('/login/authenticate', 'authenticate')->name('auth.authenticate');
});

Route::controller(ForgotPasswordController::class)->group(function () {
    Route::get('/auth/forgot-password', 'index')->name('auth.forgot-password');
    Route::post('/forgot-password', 'store')->name('auth.reset-password');
});

Route::get('/team/register', [TeamInviteController::class, 'register'])->name('team.register');
Route::post('/team/register', [TeamInviteController::class, 'acceptInvite'])->name('team.acceptInvite');

Route::get('/auth/bundle-access', [RegisterController::class, 'bundleSignup'])->name('register.bundle');
Route::post('/auth/bundle-access', [RegisterController::class, 'bundleSignupAuth'])->name('register.bundle.auth');

Route::get('/create-reseller', [RegisterController::class, 'resellerSignup'])->name('register.reseller');
Route::post('/auth/reseller-access', [RegisterController::class, 'resellerSignupAuth'])->name('register.reseller.auth');

Route::get('/privacy-policy', function(){
    return view('privacy-policy');
})->name('privacy-policy');

// Authenticated Routes
Route::middleware(['auth'])->group(function(){
    Route::controller(ProfileController::class)->group(function(){
        Route::get('/profile', 'index')->name('auth.profile');
        Route::put('/profile', 'update')->name('auth.update');
        Route::put('/profile/password', 'updatePassword')->name('auth.updatePassword');
        Route::put('/profile/esp', 'updateEsp')->name('auth.updateEsp');
        Route::post('/profile/generate-token', 'generateToken')->name('auth.generateToken');
        Route::post('/profile/logout', 'logout')->name('auth.logout');
    });

    Route::controller(DashboardController::class)->group(function (){
        Route::get('/dashboard', 'index')->name('dashboard');
        Route::get('/ministats', 'ministats')->name('dashboard.ministats');
        Route::get('/piestats', 'piestats')->name('dashboard.piestats');
        Route::get('/linestats', 'linestats')->name('dashboard.linestats');
        Route::get('/barstats', 'barstats')->name('dashboard.barstats');
    });

    Route::controller(CampaignController::class)->group(function (){
        Route::get('/campaigns', 'index')->name('campaign');
        Route::get('/campaign', 'create')->name('campaign.create');
        Route::post('/campaign/store', 'store')->name('campaign.store');
        Route::post('/campaign/update/{id}', 'update')->name('campaign.update');
        Route::post('/campaign/removelist/{id}', 'removelist')->name('campaign.removelist');
        Route::delete('/campaign/delete/{id}', 'destroy')->name('campaign.delete');
        Route::post('/sequence/store', 'storeSequence')->name('sequence.store');
    });

    Route::controller(CallManagerController::class)->group(function (){
        Route::get('/calls', 'index')->name('calls');
        Route::get('/calls/reminders', 'callReminder')->name('calls.reminders');
        Route::get('/calls/{id}', 'showCallDetails')->name('calls.show');
        Route::put('/calls/status/update', 'update')->name('calls.update');
        Route::delete('/calls/status/clear', 'clearCallStatus')->name('calls.clear');
        Route::put('/calls/pending-message/update', 'updatePendingMessageWeb')->name('calls.update-pending-message');
        Route::get('/call/reminder-messages/{id}', 'show')->name('calls.show.reminder-message');
        Route::put('/call/reminder/update', 'updateCallReminder')->name('calls.update.reminder-message');
    });

    Route::controller(App\Http\Controllers\CallCampaignController::class)->group(function (){
        Route::post('/calls/campaigns', 'store')->name('calls.campaigns.store');
        Route::post('/calls/campaigns/{id}/status', 'updateStatus')->name('calls.campaigns.status');
        Route::delete('/calls/campaigns/{id}', 'destroy')->name('calls.campaigns.delete');
    });

    Route::controller(LeadController::class)->group(function (){
        Route::get('/leadlist', 'index')->name('leads.list');
        Route::get('/leadlist/search', 'search_leadlist')->name('leads.list.search');
        Route::get('/leads/export', 'export')->name('leads.export');
        Route::get('/leads/export/bulk', 'bulk_export')->name('leads.bulk_export');
        Route::get('/leads/seach', 'search_leads')->name('leads.search_leads');
        Route::get('/leads/daily-limit', 'getDailyLimit')->name('leads.daily-limit'); // Must come before /leads/{listId}
        Route::get('/leads/pending-count', 'getPendingCount')->name('leads.pending-count'); // Must come before /leads/{listId}
        Route::get('/leads/{listId}', 'show')->name('leads.show');
        Route::put('/leadlist/update/{listId}', 'update')->name('leads.update');
        Route::delete('/leadlist/remove/{listId}', 'remove_leadlist')->name('leads.remove_leadlist');
        Route::delete('/leads/remove/{leadId}', 'remove_lead')->name('leads.remove_lead');
        Route::delete('/leads/remove/bulk/{listId}', 'remove_lead_bulk')->name('leads.remove_lead_bulk');
        Route::post('/leads/{listId}/fetch-email', 'fetchEmail')->name('leads.fetch-email');
        Route::post('/leads/{listId}/fetch-email-batch', 'fetchEmailBatch')->name('leads.fetch-email-batch');
        Route::get('/leads/{listId}/check-email/{audienceListId}', 'checkEmail')->name('leads.check-email');
    });

    Route::controller(AiwriterController::class)->group(function (){
        Route::get('/ai-contents', 'index')->name('aiwriter.index');
        Route::get('/ai-content/new', 'create')->name('aiwriter.create');
        Route::post('/ai-content/store', 'store')->name('aiwriter.store');
        Route::get('/ai-content/edit/{id}', 'edit')->name('aiwriter.edit');
        Route::put('/ai-content/update/{id}', 'update')->name('aiwriter.update');
        Route::delete('/ai-content/delete/{id}', 'destroy')->name('aiwriter.delete');
        Route::post('/ai-content/generate', 'generate')->name('aiwriter.generate');
    });

    // Content Creator Routes (New Standalone Feature)
    Route::controller(ContentCreatorController::class)->group(function (){
        Route::get('/content-creator', 'index')->name('content-creator.index');
        Route::get('/content-creator/create', 'create')->name('content-creator.create');
        Route::post('/content-creator/store', 'store')->name('content-creator.store');
        Route::post('/content-creator/generate', 'generate')->name('content-creator.generate');
        Route::post('/content-creator/improve', 'improvePost')->name('content-creator.improve');
        Route::post('/content-creator/rewrite', 'rewrite')->name('content-creator.rewrite');
        Route::get('/content-creator/templates', 'getTemplates')->name('content-creator.templates');
        Route::post('/content-creator/schedule/{id}', 'schedule')->name('content-creator.schedule');
        Route::post('/content-creator/publish/{id}', 'publish')->name('content-creator.publish');
        Route::delete('/content-creator/delete/{id}', 'destroy')->name('content-creator.delete');
        Route::post('/content-creator/bulk-delete', 'bulkDelete')->name('content-creator.bulk-delete');
        Route::get('/content-creator/analytics/{id}', 'analytics')->name('content-creator.analytics');
    });

    // Competitor Followers (LinkedIn) Feature
    Route::controller(App\Http\Controllers\LinkedInCompetitorController::class)->group(function(){
        Route::get('/competitor-followers', 'index')->name('competitor-followers.index');
        Route::post('/competitor-followers/fetch', 'fetch')->name('competitor-followers.fetch');
        Route::get('/competitor-followers/daily-limit', 'getDailyLimit')->name('competitor-followers.daily-limit');
        Route::get('/competitor-followers/pending-count', 'getPendingCount')->name('competitor-followers.pending-count');
        Route::get('/competitor-followers/{audienceId}', 'show')->name('competitor-followers.show');
        Route::get('/competitor-followers/{audienceId}/export', 'exportCsv')->name('competitor-followers.export');
        Route::post('/competitor-followers/{audienceId}/fetch-email', 'fetchEmail')->name('competitor-followers.fetch-email');
        Route::post('/competitor-followers/{audienceId}/fetch-email-batch', 'fetchEmailBatch')->name('competitor-followers.fetch-email-batch');
        Route::get('/competitor-followers/{audienceId}/check-email/{audienceListId}', 'checkEmail')->name('competitor-followers.check-email');
        Route::get('/competitor-followers/{audienceId}/status', 'getFetchStatus')->name('competitor-followers.status');
        Route::delete('/competitor-followers/{audienceId}/delete', 'delete')->name('competitor-followers.delete');
    });

    // Inspiration Library Routes (Viral Posts Discovery)
    Route::controller(App\Http\Controllers\InspirationController::class)->group(function (){
        Route::get('/inspiration', 'index')->name('inspiration.index');
        Route::post('/inspiration/preferences', 'updatePreferences')->name('inspiration.preferences.update');
        Route::post('/inspiration/store', 'storeFromWeb')->name('inspiration.store');
        Route::delete('/inspiration/delete/{id}', 'destroy')->name('inspiration.delete');
        Route::post('/inspiration/favorite/{id}', 'toggleFavorite')->name('inspiration.favorite');
        Route::get('/inspiration/use/{id}', 'useAsInspiration')->name('inspiration.use');
        Route::post('/inspiration/remix/{id}', 'remix')->name('inspiration.remix');
        Route::get('/inspiration/categories', 'getCategories')->name('inspiration.categories');
        Route::post('/inspiration/fetch', 'triggerFetch')->name('inspiration.fetch');
        Route::get('/inspiration/fetch/status', 'getFetchStatus')->name('inspiration.fetch.status');
    });

    Route::controller(SchedulePostController::class)->group(function (){
        Route::get('/posts', 'index')->name('post.index');
        Route::get('/post/new', 'create')->name('post.create');
        Route::post('/post/store', 'store')->name('post.store');
        Route::get('/post/edit/{id}', 'edit')->name('post.edit');
        Route::put('/post/update/{id}', 'update')->name('post.update');
        Route::delete('/post/delete/{id}', 'destroy')->name('post.delete');
        Route::post('/post/generate-aicontent', 'generateAiContent')->name('post.aigenerate');
    });

    Route::controller(SocialAccountController::class)->group(function (){
        Route::get('/social-account', 'index')->name('social-account.index');
        Route::post('/social-account/{integration}/credentials', 'storeCredentials')->name('social-account.credentials');
        Route::delete('/social-account/disconnect/{id}', 'disconnect')->name('social-account.disconnect');
    });

    Route::controller(IntegrationController::class)->group(function (){
        Route::get('/integration/linkedin/login', 'login')->name('integration.login');
        Route::get('/integration/linkedin/callback', 'callback')->name('integration.callback');
    });

    Route::controller(CalendlyController::class)->group(function (){
        Route::get('/oauth/calendly', 'redirect')->name('calendly.connect');
        Route::get('/oauth/calendly/callback', 'callback')->name('calendly.callback');
        Route::post('/calendly/disconnect', 'disconnect')->name('calendly.disconnect');
        Route::get('/calendly/status', 'status')->name('calendly.status');
    });

    Route::controller(TeamController::class)->group(function (){
        Route::get('/team', 'index')->name('team.index');
        Route::delete('/team/delete', 'destory')->name('team.delete');
    });

    Route::controller(TeamInviteController::class)->group(function (){
        Route::post('/team/send-invite', 'sendInvite')->name('team.sendInvite');
        Route::post('/team/resend-invite/{id}', 'resendInvite')->name('team.resendInvite');
        Route::delete('/team/delete-invite/{id}', 'destory')->name('team.deleteInvite');
    });

    Route::get('/tutorials', function(){
        return view('tutorial');
    })->name('tutorials');

    Route::get('/upsell-unlimited', function(){
        return view('bonus.unlimited');
    })->name('upsell-unlimited');

    Route::get('/market-agency-setup', function(){
        return view('bonus.dfyMarketAgencySetup');
    })->name('market-agency-setup');

    Route::get('/dfy-campaign', function(){
        return view('bonus.dfyCampaign');
    })->name('dfy-campaign');

    Route::get('/dfy-software-empire-setup', function(){
        return view('bonus.dfySoftwareEmpireSetup');
    })->name('dfy-software-empire-setup');

    Route::get('/coach-program', function(){
        return view('bonus.coachProgram');
    })->name('coach-program');

    Route::get('/unlimited-traffic', function(){
        return view('bonus.unlimitedTraffic');
    })->name('unlimited-traffic');

    // Admin 
    Route::controller(UserManagerController::class)->group(function (){
        Route::get('/admin/users', 'index')->name('users.index');
        Route::post('/admin/user/store', 'store')->name('user.store');
        Route::put('/admin/user/update/{id}', 'update')->name('user.update');
        Route::delete('/admin/user/delete/{id}', 'destroy')->name('user.delete');
        Route::get('/admin/user/permissions', 'userPermissions')->name('user.permissions');
        Route::put('/admin/user/assign-permissions', 'assignPermissions')->name('user.assign-permissions');

        Route::get('/reseller/users', 'resellerIndex')->name('reseller.index');
    });

    Route::controller(CommentFeedController::class)->group(function (){
        Route::get('/comment', 'index')->name('comment.index');
        Route::get('/comment/campaign/create', 'createCampaign')->name('comment.create-campaign');
        Route::post('/comment/campaign/store', 'storeCampaign')->name('comment.store-campaign');
        Route::put('/comment/campaign/update/{id}', 'updateCampaignStatus')->name('comment.update-campaign-status');
        Route::delete('/comment/campaign/delete/{id}', 'destroyCampaign')->name('comment.delete-campaign');
        Route::post('/comment/skip', 'skipComment')->name('comment.skip');
        Route::post('/comment/generate', 'generateComment')->name('comment.generate');
    });

    Route::controller(App\Http\Controllers\AutoCommentController::class)->group(function (){
        Route::get('/auto-comment', 'index')->name('auto-comment.index');
        Route::get('/auto-comment/preferences', 'preferences')->name('auto-comment.preferences');
        Route::post('/auto-comment/preferences', 'storePreferences')->name('auto-comment.store-preferences');
        Route::delete('/auto-comment/post/{id}', 'deletePost')->name('auto-comment.delete-post');
    });
});

Route::post('/comment/campaign-activities/generate', [CommentFeedController::class, 'generateWebhook']);


Route::post('/ipn/jvzoo', [JVZooWebhookController::class, 'JVZoo'])->name('ipn.jvzoo');