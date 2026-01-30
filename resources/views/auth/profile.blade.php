@extends('layout.auth')
@seo([
        'title' => 'Profile - ' . config('app.name'),
        'description' => 'LinkedIn Lead Generation And Sales Automation Tool',
        'image' => asset('images/site-image.png'),
        'site_name' => config('app.name'),
        'favicon' => asset('images/logo-1.png'),
    ])
@section('content')
<div>
    <h2 class="text-2xl font-semibold text-gray-900">
        Account settings
    </h2>
    <div class="grid gap-3 mb-8 md:grid-cols-2 mt-4">
        <div class="min-w-0 p-4 bg-white rounded-lg shadow-xs">
            <h4 class="mb-4 font-semibold text-gray-900">
                Personal Information
            </h4>
            <p class="text-gray-600">
                Update your account's profile information and email address.
            </p>
            <form action="{{route('auth.update')}}" method="post" class="mt-6">
                @csrf
                @method('PUT')
                <label class="block text-sm">
                    <span class="text-gray-700">Name</span>
                    <input
                    type="text"
                    class="block w-full mt-1 text-sm focus:border-[#0077b5] focus:outline-none 
                    focus:ring-2 focus:ring-[#0077b5] form-input rounded-md border-gray-300"
                    id="name" name="name" value="{{ auth()->user()->name }}" 
                    placeholder="Name" 
                    required
                    />
                </label>
                <label class="block text-sm mt-6">
                    <span class="text-gray-700">Email</span>
                    <input
                    type="email"
                    class="block w-full mt-1 text-sm focus:border-[#0077b5] focus:outline-none 
                    focus:ring-2 focus:ring-[#0077b5] form-input rounded-md border-gray-300"
                    placeholder="email@example.com"
                    id="email" name="email" value="{{ auth()->user()->email }}"
                    required
                    readonly
                    />
                </label>
                <label class="block text-sm mt-6">
                    <span class="text-gray-700">LinkedIn profile ID</span>
                    <input
                    type="text"
                    class="block w-full mt-1 text-sm focus:border-[#0077b5] focus:outline-none 
                    focus:ring-2 focus:ring-[#0077b5] form-input rounded-md border-gray-300"
                    id="linkedin-id" name="linkedin_id" value="{{ auth()->user()->linkedin_id }}" 
                    placeholder="LinkedIn ID"
                    />
                </label>
                <label class="block text-sm mt-6">
                    <span class="text-gray-700">Time zone</span>
                    <select class="block w-full mt-1 text-sm rounded-md
                    form-select focus:border-[#0077b5] 
                    focus:outline-none focus:ring-2 focus:ring-[#0077b5] border-gray-300"
                    id="timezone"
                    name="timezone">
                        @foreach($timezones as $item)
                        <option value="{{$item->id}}" {{$item->id == auth()->user()->time_zone_id ? 'selected':'' }}>
                            {{$item->time_zone}}
                        </option>
                        @endforeach
                    </select>
                </label>
                <button 
                type="submit"
                class="block w-full px-4 py-2 mt-6 text-sm font-medium 
                leading-5 text-center text-white transition-all duration-150 
                border border-transparent rounded-lg"
                style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);"
                onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';"
                onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">
                    Save
                </button>
            </form>
        </div>
        <div class="min-w-0 p-4 bg-white rounded-lg shadow-xs">
            <h4 class="mb-4 font-semibold text-gray-900">
                Update Password
            </h4>
            <p class="text-gray-600">
                Ensure your account is using a long, random password to stay secure.
            </p>
            <form action="{{ route('auth.updatePassword') }}" method="post">
                @csrf
                @method('PUT')
                <label class="block mt-6 text-sm">
                    <span class="text-gray-700">Current password</span>
                    <input
                    type="password"
                    class="block w-full mt-1 text-sm focus:border-[#0077b5] focus:outline-none 
                    focus:ring-2 focus:ring-[#0077b5] form-input rounded-md border-gray-300"
                    placeholder="***************"
                    id="current-password" name="old_password" 
                    required
                    />
                </label>
                <label class="block mt-6 text-sm">
                    <span class="text-gray-700">New password</span>
                    <input
                    type="password"
                    class="block w-full mt-1 text-sm focus:border-[#0077b5] focus:outline-none 
                    focus:ring-2 focus:ring-[#0077b5] form-input rounded-md border-gray-300"
                    placeholder="***************"
                    id="new-password" name="new_password" 
                    required
                    />
                </label>
                <label class="block mt-6 text-sm">
                    <span class="text-gray-700">Confirm new password</span>
                    <input
                    type="password"
                    class="block w-full mt-1 text-sm focus:border-[#0077b5] focus:outline-none 
                    focus:ring-2 focus:ring-[#0077b5] form-input rounded-md border-gray-300"
                    placeholder="***************"
                    id="confirm-password" name="confirm_password"
                    required
                    />
                </label>
                <button 
                type="submit"
                class="block w-full px-4 py-2 mt-6 text-sm font-medium 
                leading-5 text-center text-white transition-all duration-150 
                border border-transparent rounded-lg"
                style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);"
                onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';"
                onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">
                    Save
                </button>
            </form>
        </div>
    </div>
    <div class="grid gap-3 mb-8 mt-3 md:grid-cols-2">
        <div class="min-w-0 p-4 bg-white rounded-lg shadow-xs">
            <div class="px-4 py-5">
                <div>
                    <div class="px-4 sm:px-0">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">
                            Email Integration
                        </h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Set-up an email integration here and your emails will directly get pushed into your ESP.
                        </p>
                    </div>

                    <div class="md:flex flex-nowrap gap-1">
                        <button 
                        type="button"
                        class="block px-2 py-2 mt-6 text-xs font-medium mailchimp
                        leading-5 text-center text-white transition-all duration-150 
                        border border-transparent rounded-lg"
                        style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);"
                        onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';"
                        onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">
                        MailChimp
                        </button>
                        <button 
                        type="button"
                        class="block px-2 py-2 mt-6 text-xs font-medium getresponse
                        leading-5 text-center text-white transition-all duration-150 
                        border border-transparent rounded-lg"
                        style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);"
                        onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';"
                        onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">
                        Get Response
                        </button>
                        <button 
                        type="button"
                        class="block px-2 py-2 mt-6 text-xs font-medium emailoctopus
                        leading-5 text-center text-white transition-all duration-150 
                        border border-transparent rounded-lg"
                        style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);"
                        onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';"
                        onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">
                        Email Octopus
                        </button>
                        <button 
                        type="button"
                        class="block px-2 py-2 mt-6 text-xs font-medium converterkit
                        leading-5 text-center text-white transition-all duration-150 
                        border border-transparent rounded-lg"
                        style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);"
                        onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';"
                        onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">
                        ConverterKit
                        </button>
                        <button 
                        type="button"
                        class="block px-2 py-2 mt-6 text-xs font-medium mailerlite
                        leading-5 text-center text-white transition-all duration-150 
                        border border-transparent rounded-lg"
                        style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);"
                        onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';"
                        onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">
                        Mailerlite
                        </button>
                        <button 
                        type="button"
                        class="block px-2 py-2 mt-6 text-xs font-medium webhook
                        leading-5 text-center text-white transition-all duration-150 
                        border border-transparent rounded-lg"
                        style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);"
                        onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';"
                        onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">
                        Webhook
                        </button>
                    </div>
                    <!-- fields -->
                    <form action="{{route('auth.updateEsp')}}" method="post">
                        @csrf
                        @method('PUT')
                        <div class="flex gap-4 mt-6 mailchimp-input esp-input hidden">
                            <label class="block text-sm w-full mb-4">
                                <span class="text-gray-700">Mailchimp API Key</span>
                                <input
                                type="text"
                                class="block w-full mt-1 text-sm focus:border-[#0077b5] focus:outline-none 
                                focus:ring-2 focus:ring-[#0077b5] form-input rounded-md border-gray-300"
                                id="mailchimp_key" name="mailchimp_key"
                                value="{{$esp?->mailchimp?->apikey}}"/>
                                <p class="mt-1 text-gray-500 text-xs">
                                    Leave Empty to disable this integration
                                </p>
                            </label>
                            <label class="block text-sm w-full">
                                <span class="text-gray-700">Mailchimp List ID</span>
                                <input
                                type="text"
                                class="block w-full mt-1 text-sm focus:border-[#0077b5] focus:outline-none 
                                focus:ring-2 focus:ring-[#0077b5] form-input rounded-md border-gray-300"
                                id="mailchimp_listid" name="mailchimp_listid"
                                value="{{$esp?->mailchimp?->listid}}"
                                placeholder="e.g., 3c54a618ea"/>
                                <p class="mt-1 text-gray-500 text-xs">
                                    Ensure you add the List/Audience ID. Format: <span class="font-mono text-gray-600">3c54a618ea</span> (alphanumeric, lowercase)
                                </p>
                            </label>
                        </div>
                        <div class="flex gap-4 mt-6 getresponse-input esp-input hidden">
                            <label class="block text-sm w-full mb-4">
                                <span class="text-gray-700">GetResponse API Key</span>
                                <input
                                type="text"
                                class="block w-full mt-1 text-sm focus:border-[#0077b5] focus:outline-none 
                                focus:ring-2 focus:ring-[#0077b5] form-input rounded-md border-gray-300"
                                id="getresponse_key" name="getresponse_key"
                                value="{{$esp?->getresponse?->apikey}}"/>
                                <p class="mt-1 text-gray-500 text-xs">
                                    Leave Empty to disable this integration
                                </p>
                            </label>
                            <label class="block text-sm w-full">
                                <span class="text-gray-700">GetResponse Campaign ID</span>
                                <input
                                type="text"
                                class="block w-full mt-1 text-sm focus:border-[#0077b5] focus:outline-none 
                                focus:ring-2 focus:ring-[#0077b5] form-input rounded-md border-gray-300"
                                id="getresponse_campaignid" name="getresponse_campaignid"
                                value="{{$esp?->getresponse?->campaignId}}"
                                placeholder="e.g., L4PIF"/>
                                <p class="mt-1 text-gray-500 text-xs">
                                    Campaign token/ID from GetResponse. Format: <span class="font-mono text-gray-600">L4PIF</span> (alphanumeric, usually uppercase letters)
                                </p>
                            </label>
                        </div>
                        <div class="flex gap-4 mt-6 emailoctopus-input esp-input hidden">
                            <label class="block text-sm w-full mb-4">
                                <span class="text-gray-700">Email Octopus API Key</span>
                                <input
                                type="text"
                                class="block w-full mt-1 text-sm focus:border-[#0077b5] focus:outline-none 
                                focus:ring-2 focus:ring-[#0077b5] form-input rounded-md border-gray-300"
                                id="emailoctopus_key" name="emailoctopus_key"
                                value="{{$esp?->emailoctopus?->apikey}}"/>
                                <p class="mt-1 text-gray-500 text-xs">
                                    Leave Empty to disable this integration
                                </p>
                            </label>
                            <label class="block text-sm w-full">
                                <span class="text-gray-700">Email Octopus List ID</span>
                                <input
                                type="text"
                                class="block w-full mt-1 text-sm focus:border-[#0077b5] focus:outline-none 
                                focus:ring-2 focus:ring-[#0077b5] form-input rounded-md border-gray-300"
                                id="emailoctopus_listid" name="emailoctopus_listid"
                                value="{{$esp?->emailoctopus?->listid}}"
                                placeholder="e.g., a1b2c3d4-e5f6-7890-abcd-ef1234567890"/>
                                <p class="mt-1 text-gray-500 text-xs">
                                    List ID from EmailOctopus. Format: <span class="font-mono text-gray-600">a1b2c3d4-e5f6-7890-abcd-ef1234567890</span> (UUID format)
                                </p>
                            </label>
                        </div>
                        <div class="flex gap-4 mt-6 converterkit-input esp-input hidden">
                            <label class="block text-sm w-full mb-4">
                                <span class="text-gray-700">ConverterKit API Key</span>
                                <input
                                type="text"
                                class="block w-full mt-1 text-sm focus:border-[#0077b5] focus:outline-none 
                                focus:ring-2 focus:ring-[#0077b5] form-input rounded-md border-gray-300"
                                id="converterkit_key" name="converterkit_key"
                                value="{{$esp?->converterkit?->apikey}}"/>
                                <p class="mt-1 text-gray-500 text-xs">
                                    Leave Empty to disable this integration
                                </p>
                            </label>
                            <label class="block text-sm w-full">
                                <span class="text-gray-700">ConverterKit Form ID</span>
                                <input
                                type="text"
                                class="block w-full mt-1 text-sm focus:border-[#0077b5] focus:outline-none 
                                focus:ring-2 focus:ring-[#0077b5] form-input rounded-md border-gray-300"
                                id="converterkit_formid" name="converterkit_formid"
                                value="{{$esp?->converterkit?->formId}}"
                                placeholder="e.g., 1234567"/>
                                <p class="mt-1 text-gray-500 text-xs">
                                    Form ID from ConverterKit. Format: <span class="font-mono text-gray-600">1234567</span> (numeric ID)
                                </p>
                            </label>
                        </div>
                        <div class="flex gap-4 mt-6 mailerlite-input esp-input hidden">
                            <label class="block text-sm w-full mb-4">
                                <span class="text-gray-700">MailerLite API Key</span>
                                <input
                                type="text"
                                class="block w-full mt-1 text-sm focus:border-[#0077b5] focus:outline-none 
                                focus:ring-2 focus:ring-[#0077b5] form-input rounded-md border-gray-300"
                                id="mailerlite_key" name="mailerlite_key"
                                value="{{$esp?->mailerlite?->apikey}}"/>
                                <p class="mt-1 text-gray-500 text-xs">
                                    Leave Empty to disable this integration
                                </p>
                            </label>
                            <label class="block text-sm w-full">
                                <span class="text-gray-700">MailerLite Group ID</span>
                                <input
                                type="text"
                                class="block w-full mt-1 text-sm focus:border-[#0077b5] focus:outline-none 
                                focus:ring-2 focus:ring-[#0077b5] form-input rounded-md border-gray-300"
                                id="mailerlite_groupid" name="mailerlite_groupid"
                                value="{{$esp?->mailerlite?->groupId}}"
                                placeholder="e.g., 123456789"/>
                                <p class="mt-1 text-gray-500 text-xs">
                                    Group ID from MailerLite. Format: <span class="font-mono text-gray-600">123456789</span> (numeric ID)
                                </p>
                            </label>
                        </div>
                        <div class="flex gap-4 mt-6 webhook-input esp-input hidden">
                            <label class="block text-sm w-full">
                                <span class="text-gray-700">Webhook URL</span>
                                <input
                                type="url"
                                class="block w-full mt-1 text-sm focus:border-[#0077b5] focus:outline-none 
                                focus:ring-2 focus:ring-[#0077b5] form-input rounded-md border-gray-300"
                                id="webhook" name="webhook"
                                value="{{$esp?->webhook}}"/>
                                <p class="mt-1 text-gray-500 text-xs">
                                    Leave Empty to disable this integration
                                </p>
                            </label>
                        </div>

                        <button 
                        type="submit"
                        class="block px-4 py-2 mt-6 text-sm font-medium 
                        leading-5 text-center text-white transition-all duration-150 
                        border border-transparent rounded-lg"
                        style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);"
                        onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';"
                        onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">
                        Save
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="min-w-0 p-4 bg-white rounded-lg shadow-xs">
            <div class="px-4 sm:px-0">
                <h3 class="text-lg font-medium leading-6 text-gray-900">
                    Api Key
                </h3>
                <p class="mt-1 text-sm text-gray-600">
                    Manage your API key here.
                </p>
            </div>
            <div class="mt-4">
                <form action="{{route('auth.generateToken')}}" method="post">
                    @csrf
                    <label class="block text-sm w-full mb-4">
                        <input
                        type="text"
                        class="block w-full mt-1 text-sm focus:border-[#0077b5] focus:outline-none 
                        focus:ring-2 focus:ring-[#0077b5] form-input rounded-md border-gray-300"
                        id="api_token" name="api_token"
                        value="{{auth()->user()->access_token}}"
                        disabled/>
                    </label>
                    <button 
                        type="submit"
                        class="block px-2 py-2 mt-3 text-xs font-medium
                        leading-5 text-center text-white transition-all duration-150 
                        border border-transparent rounded-lg"
                        style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);"
                        onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';"
                        onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">
                        {{ auth()->user()->access_token ? 'Regenerate':'Generate'}}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    $('.mailchimp').click(function() {
        $('.esp-input').hide()
        $('.mailchimp-input').show()
    })
    $('.getresponse').click(function() {
        $('.esp-input').hide()
        $('.getresponse-input').show()
    })
    $('.emailoctopus').click(function() {
        $('.esp-input').hide()
        $('.emailoctopus-input').show()
    })
    $('.converterkit').click(function() {
        $('.esp-input').hide()
        $('.converterkit-input').show()
    })
    $('.mailerlite').click(function() {
        $('.esp-input').hide()
        $('.mailerlite-input').show()
    })
    $('.webhook').click(function() {
        $('.esp-input').hide()
        $('.webhook-input').show()
    })
</script>
@endsection