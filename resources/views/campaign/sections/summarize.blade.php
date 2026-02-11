<form method="post" action="{{route('campaign.update', ['id' => $cid])}}">
    @csrf
    @php
        $processConditions = json_decode($campaign->process_condition ?? '[]', true) ?: [];
    @endphp
    <div class="grid grid-cols-12 gap-6 pt-4 pb-8">
        <div class="col-span-6">
            <div class="flex justify-between">
                <label for="campaign-name" class="block text-sm font-medium leading-6 text-gray-900 w-1/2">Campaign name</label>
                <input type="text" name="campaign_name" id="campaign-name" value="{{$campaign->name}}" autocomplete="campaign-name" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-1 focus:ring-inset focus:ring-[#0077b5] sm:text-sm sm:leading-6">
            </div>
        </div>
        <div class="col-span-6">
            <label for="campaign-name" class="block text-sm font-medium leading-6 text-gray-900 w-1/2">Do not process if</label>
            <div class="mt-4 space-y-6">
                <div class="relative flex gap-x-4">
                    <div class="flex h-6 items-center">
                        <input id="lead_on_other_campaign" name="process_condition[]" value="lead_on_other_campaign" type="checkbox" class="h-4 w-4 rounded border-gray-300 focus:ring-[#0077b5]" style="accent-color: #0077b5;" {{ in_array('lead_on_other_campaign', $processConditions, true) ? 'checked' : '' }}>
                    </div>
                    <div class="text-sm leading-6">
                        <label for="lead_on_other_campaign" class="font-medium text-gray-900">
                            Same lead found on other campaigns (recommended)
                        </label>
                    </div>
                </div>
                <div class="relative flex gap-x-4">
                    <div class="flex h-6 items-center">
                        <input id="no_profile_photo" name="process_condition[]" value="no_profile_photo" type="checkbox" class="h-4 w-4 rounded border-gray-300 focus:ring-[#0077b5]" style="accent-color: #0077b5;" {{ in_array('no_profile_photo', $processConditions, true) ? 'checked' : '' }}>
                    </div>
                    <div class="text-sm leading-6">
                        <label for="no_profile_photo" class="font-medium text-gray-900">
                            No photo on leads profile
                        </label>
                    </div>
                </div>
                <div class="relative flex gap-x-4">
                    <div class="flex h-6 items-center">
                        <input id="less_than_500_connect" name="process_condition[]" value="less_than_500_connect" type="checkbox" class="h-4 w-4 rounded border-gray-300 focus:ring-[#0077b5]" style="accent-color: #0077b5;" {{ in_array('less_than_500_connect', $processConditions, true) ? 'checked' : '' }}>
                    </div>
                    <div class="text-sm leading-6">
                        <label for="less_than_500_connect" class="font-medium text-gray-900">
                            Less than 500 connections
                        </label>
                    </div>
                </div>
                <div class="relative flex gap-x-4">
                    <div class="flex h-6 items-center">
                        <input id="free_linkedin_account" name="process_condition[]" value="free_linkedin_account" type="checkbox" class="h-4 w-4 rounded border-gray-300 focus:ring-[#0077b5]" style="accent-color: #0077b5;" {{ in_array('free_linkedin_account', $processConditions, true) ? 'checked' : '' }}>
                    </div>
                    <div class="text-sm leading-6">
                        <label for="free_linkedin_account" class="font-medium text-gray-900">
                            Having free LinkedIn account
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="flex justify-center pt-10 gap-3">
        <a href="{{route('campaign.create', ['step' => 'sequence', 'cid' => $cid])}}"
            class="block px-10 py-3 text-sm font-medium leading-2 
            text-white transition-colors duration-150 bg-gray-400 
            border border-transparent rounded-tr-lg rounded-bl-lg active:bg-gray-600
            hover:bg-gray-600 focus:outline-none focus:shadow-outline-gray">
            <span class="flex gap-1">
                Back
            </span>
        </a>
        <button type="submit"
            class="block px-10 py-3 text-sm font-medium leading-2 
            text-white transition-all duration-150 
            border border-transparent rounded-tr-lg rounded-bl-lg focus:outline-none submit-profileview-sequence" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">
            <span class="flex gap-1">
                <span class="pt-1">Save</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
            </span>
        </button>
    </div>
</form>