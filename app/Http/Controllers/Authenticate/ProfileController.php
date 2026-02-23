<?php

namespace App\Http\Controllers\Authenticate;

use App\Rules\MatchOldPassword;
use App\Models\Timezone;
use App\Models\User;
use App\Models\EspIntegration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function index()
    {
        $timezones = Timezone::select('id', 'time_zone')->get();
        $esp = EspIntegration::where('user_id', auth()->user()->id)->first();

        if($esp){
            $esp = (object)[
                'id' => $esp->id,
                'mailchimp' => json_decode($esp->mailchimp),
                'getresponse' => json_decode($esp->getresponse),
                'emailoctopus' => json_decode($esp->emailoctopus),
                'converterkit' => json_decode($esp->converterkit),
                'mailerlite' => json_decode($esp->mailerlite),
                'webhook' => $esp->webhook
            ];
        }

        return view('auth.profile', compact('timezones','esp'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['required'],
            'email' => ['required','email'],
            'linkedin_id' => ['required', 'string', 'max:255'],
            'timezone' => ['required']
        ]);

        User::where('id', auth()->user()->id)
            ->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'linkedin_id' => $data['linkedin_id'],
                'time_zone_id' => $data['timezone'],
            ]);

        notify()->success('Profile updated successfully');
        return redirect()->route('auth.profile');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'old_password' => ['required', new MatchOldPassword],
            'new_password' => ['required'],
            'confirm_password' => ['same:new_password']
        ]);

        User::where('id', auth()->user()->id)
            ->update([
                'password' => bcrypt($request->new_password)
            ]);

        notify()->success('Password updated successfully');
        return redirect()->route('auth.profile');
    }

    public function updateEsp(Request $request)
    {
        $data = $request->all();

        EspIntegration::updateOrCreate(
            [ 'user_id' => auth()->user()->id ],
            [
                'mailchimp' => json_encode(['apikey'=> $request->mailchimp_key, 'listid' => $request->mailchimp_listid]),
                'getresponse' => json_encode(['apikey'=> $request->getresponse_key, 'campaignId' => $request->getresponse_campaignid]),
                'emailoctopus' => json_encode(['apikey'=> $request->emailoctopus_key, 'listid' => $request->emailoctopus_listid]),
                'converterkit' => json_encode(['apikey'=> $request->converterkit_key, 'formId' => $request->converterkit_formid]),
                'mailerlite' => json_encode(['apikey'=> $request->mailerlite_key, 'groupId' => $request->mailerlite_groupid]),
                'webhook' => $request->webhook
            ]
        );

        notify()->success('Email integration saved successfully');
        return redirect()->route('auth.profile');
    }

    public function generateToken()
    {
        $token = bin2hex(random_bytes(32));

        auth()->user()->update([
            'access_token' => $token
        ]);

        notify()->success('API token generated successfully');
        return redirect()->route('auth.profile');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
    
        $request->session()->invalidate();
    
        $request->session()->regenerateToken();
    
        return redirect()->route('auth.login');
    }
}
