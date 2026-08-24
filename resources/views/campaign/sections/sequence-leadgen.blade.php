<div>
    <h4 class="mb-2 font-semibold">Lead Generation</h4>
    <div class="p-4 mb-4 text-sm text-yellow-800 rounded-lg bg-yellow-50 flex gap-3" role="alert">    
        <span>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
            </svg>
        </span>
        You can send around 100 connection requests per week. <br>
        You can send up to 20 connection request per day on average.
    </div>
    <div class="flex gap-3">
        <div class="w-full bg-gray-100 rounded min-h-screen">
            <div id="myLeadgenDiagramDiv" style="width:100%; min-height:100%"></div>
        </div>
    </div>
    <div class="mt-4 mb-4">
        <input type="hidden" id="action-field">
        <div class="pt-3">
            <button type="button" id="applyAction" class="block px-10 py-2 text-sm font-medium leading-2 
            text-white transition-all duration-150 w-80
            border border-transparent rounded focus:outline-none" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%); display: none;" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">
                Apply
            </button>
        </div>
        <!-- Book call form -->
        <button type="button" style="display: none" class="lead-gen-modal-btn py-3 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent text-white focus:outline-hidden disabled:opacity-50 disabled:pointer-events-none transition-all" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';" aria-haspopup="dialog" aria-expanded="false" aria-controls="hs-medium-modal" data-hs-overlay="#hs-medium-modal">
            Toggle modal
        </button>
        <div id="hs-medium-modal" class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none" role="dialog" tabindex="-1" aria-labelledby="hs-medium-modal-label">
            <div class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all md:max-w-2xl md:w-full m-3 md:mx-auto">
                <div class="flex flex-col bg-white border border-gray-200 shadow-md rounded-xl pointer-events-auto">
                <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200">
                    <h3 id="hs-medium-modal-label" class="font-bold text-gray-800 modal-title">
                        Modal title
                    </h3>
                    <button type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-full border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 focus:outline-hidden focus:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none" aria-label="Close" data-hs-overlay="#hs-medium-modal">
                    <span class="sr-only">Close</span>
                    <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                    </button>
                </div>
                <div class="p-4 overflow-y-auto">
                    <!-- Delay fields -->
                    <div id="delay-fields" class="leadgen-sequence-fields w-80" style="display: none;">
                        <label class="font-medium text-gray-900">
                            Total delay time
                        </label>
                        <div class="grid grid-cols-6 gap-3 pt-2">
                            <div class="col-span-2">
                                <input type="number" id="time-number" class="border border-gray-300 text-gray-900 rounded focus:ring-[#0077b5] focus:border-[#0077b5] block w-full px-3 disabled:opacity-50 disabled:pointer-events-none">
                            </div>
                            <div class="col-span-3">
                                <select id="time-type" class="px-4 pe-9 block py-2 w-full border-gray-300 rounded focus:border-[#0077b5] focus:ring-[#0077b5] disabled:opacity-50 disabled:pointer-events-none">
                                    <option value="days">days</option>
                                    <option value="hours">hours</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <!-- Endorse skill -->
                    <div id="endorse-fields" class="leadgen-sequence-fields" style="display: none;">
                        <label for="invite-note" class="font-medium text-gray-900">
                            Total skills to endorse
                        </label>
                        <div class="grid grid-cols-6 gap-3 pt-2">
                            <div class="col-span-6">
                                <input type="number" id="total-endorse-skill" class="border border-gray-300 text-gray-900 rounded focus:ring-[#0077b5] focus:border-[#0077b5] block w-full px-3 disabled:opacity-50 disabled:pointer-events-none">
                            </div>
                        </div>
                    </div>
                    <!-- Send message -->
                    <div id="send-message-fields" class="leadgen-sequence-fields" style="display: none;">
                        <div class="flex gap-1 mt-2">
                            <div class="">
                                <button class="px-2 py-2 text-sm font-medium border border-transparent
                                text-white rounded focus:outline-none transition-all"
                                style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';"
                                onclick="addVariableToMessage('@firstName')">
                                    First name
                                </button>
                            </div>
                            <div class="">
                                <button class="px-2 py-2 text-sm font-medium border border-transparent
                                text-white rounded focus:outline-none transition-all"
                                style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';"
                                onclick="addVariableToMessage('@lastName')">
                                    Last name
                                </button>
                            </div>
                            <div class="">
                                <button class="px-2 py-2 text-sm font-medium border border-transparent
                                text-white rounded focus:outline-none transition-all"
                                style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';"
                                onclick="addVariableToMessage('@name')">
                                    Full name
                                </button>
                            </div>
                            <div>
                                <button class="px-4 py-2 text-sm font-medium border border-transparent
                                text-white rounded focus:outline-none transition-all"
                                style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';"
                                onclick="addVariableToMessage('@position')">
                                    Position
                                </button>
                            </div>
                            <div>
                                <button class="px-3 py-2 text-sm font-medium border border-transparent
                                text-white rounded focus:outline-none transition-all"
                                style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';"
                                onclick="addVariableToMessage('@company')">
                                    Company
                                </button>
                            </div>
                            <div>
                                <button class="px-3 py-2 text-sm font-medium border border-transparent
                                text-white rounded focus:outline-none transition-all"
                                style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';"
                                onclick="addVariableToMessage('@location')">
                                    Location
                                </button>
                            </div>
                        </div>
                        <div class="mt-2">
                            <textarea id="send-message" name="send_message" rows="6" class="text-xs block w-full rounded-md border-0 px-2 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-[#0077b5] sm:text-sm sm:leading-6"></textarea>
                        </div>
                    </div>

                    <!-- Send invites fields -->
                    <div id="send-invites-fields" class="leadgen-sequence-fields" style="display: none;">
                        <div class="flex gap-2">
                            <div class="">
                                <button class="px-2 py-2 text-sm font-medium border border-transparent
                                text-white rounded focus:outline-none transition-all invite-variables"
                                style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';"
                                onclick="addVariableToMessage('@firstName')" disabled>
                                    First name
                                </button>
                            </div>
                            <div class="">
                                <button class="px-2 py-2 text-sm font-medium border border-transparent
                                text-white rounded focus:outline-none transition-all invite-variables"
                                style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';"
                                onclick="addVariableToMessage('@lastName')" disabled>
                                    Last name
                                </button>
                            </div>
                            <div class="">
                                <button class="px-2 py-2 text-sm font-medium border border-transparent
                                text-white rounded focus:outline-none transition-all invite-variables"
                                style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';"
                                onclick="addVariableToMessage('@name')" disabled>
                                    Full name
                                </button>
                            </div>
                            <div>
                                <button class="px-4 py-2 text-sm font-medium border border-transparent
                                text-white rounded focus:outline-none transition-all invite-variables"
                                style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';"
                                onclick="addVariableToMessage('@position')" disabled>
                                    Position
                                </button>
                            </div>
                            <div>
                                <button class="px-3 py-2 text-sm font-medium border border-transparent
                                text-white rounded focus:outline-none transition-all invite-variables"
                                style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';"
                                onclick="addVariableToMessage('@company')" disabled>
                                    Company
                                </button>
                            </div>
                            <div>
                                <button class="px-3 py-2 text-sm font-medium border border-transparent
                                text-white rounded focus:outline-none transition-all invite-variables"
                                style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';"
                                onclick="addVariableToMessage('@location')" disabled>
                                    Location
                                </button>
                            </div>
                        </div>
                        <div class="mt-2">
                            <textarea id="invite-message" name="invite_message" rows="6" class="text-xs block w-full rounded-md border-0 px-2 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-[#0077b5] sm:text-sm sm:leading-6" disabled></textarea>
                        </div>
                        <div class="mt-4 space-y-6">
                            <div class="relative flex gap-x-4">
                                <div class="flex h-6 items-center">
                                    <input id="invite-note" name="invite_note" value="on" type="checkbox" class="h-4 w-4 rounded border-gray-300 focus:ring-[#0077b5]" style="accent-color: #0077b5;" checked>
                                </div>
                                <div class="text-sm leading-6">
                                    <label for="invite-note" class="font-medium text-gray-900">
                                        Send an invite without a note
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Commented out - Book a call will be built as a standalone feature --}}
                    {{-- <!-- Book a call -->
                    <div id="book-call-fields" class="leadgen-sequence-fields" style="display: none;">
                        <div class="flex gap-2">
                            <div class="">
                                <button class="px-2 py-2 text-sm font-medium border border-transparent
                                text-white rounded focus:outline-none transition-all"
                                style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';"
                                onclick="addVariableToMessage('@firstName')">
                                    First name
                                </button>
                            </div>
                            <div class="">
                                <button class="px-2 py-2 text-sm font-medium border border-transparent
                                text-white rounded focus:outline-none transition-all"
                                style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';"
                                onclick="addVariableToMessage('@lastName')">
                                    Last name
                                </button>
                            </div>
                            <div class="">
                                <button class="px-2 py-2 text-sm font-medium border border-transparent
                                text-white rounded focus:outline-none transition-all"
                                style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';"
                                onclick="addVariableToMessage('@name')">
                                    Full name
                                </button>
                            </div>
                            <div>
                                <button class="px-4 py-2 text-sm font-medium border border-transparent
                                text-white rounded focus:outline-none transition-all"
                                style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';"
                                onclick="addVariableToMessage('@position')">
                                    Position
                                </button>
                            </div>
                            <div>
                                <button class="px-3 py-2 text-sm font-medium border border-transparent
                                text-white rounded focus:outline-none transition-all"
                                style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';"
                                onclick="addVariableToMessage('@company')">
                                    Company
                                </button>
                            </div>
                            <div>
                                <button class="px-3 py-2 text-sm font-medium border border-transparent
                                text-white rounded focus:outline-none transition-all"
                                style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';"
                                onclick="addVariableToMessage('@location')">
                                    Location
                                </button>
                            </div>
                        </div>
                        <div class="mt-2 call-message-space" style="display: none;">
                            <div class="mb-3">
                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" id="use-ai-paraphrase" name="use_ai_paraphrase" class="rounded border-gray-300 shadow-sm focus:border-[#0077b5] focus:ring focus:ring-[#0077b5] focus:ring-opacity-50" style="accent-color: #0077b5;">
                                    <span>Use AI (paraphrase my message)</span>
                                </label>
                                <p class="text-xs text-gray-500 mt-1">If checked, AI will improve and paraphrase your message. If unchecked, your exact message will be sent.</p>
                            </div>
                            
                            <!-- AI Mode Selection -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">AI Mode</label>
                                <div class="space-y-2">
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input type="radio" id="ai-mode-auto" name="ai_mode" value="auto" class="focus:ring-[#0077b5]" style="accent-color: #0077b5;" checked>
                                        <span>AI Mode (Auto Send)</span>
                                    </label>
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input type="radio" id="ai-mode-review" name="ai_mode" value="review" class="focus:ring-[#0077b5]" style="accent-color: #0077b5;">
                                        <span>Review Mode (Hold for Review)</span>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Review Time Input (shown when review mode is selected) -->
                            <div id="review-time-container" class="mb-3" style="display: none;">
                                <label for="review-time" class="block text-sm font-medium text-gray-700 mb-1">Review Time (minutes)</label>
                                <input type="number" id="review-time" name="review_time" min="1" max="1440" value="30" class="text-xs block w-full rounded-md border-0 px-2 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-[#0077b5] sm:text-sm sm:leading-6" placeholder="Enter review time in minutes">
                                <p class="text-xs text-gray-500 mt-1">Messages will be held for review and sent after this time period.</p>
                            </div>
                            
                            <textarea id="call-message" name="call_message" rows="6" class="text-xs block w-full rounded-md border-0 px-2 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-[#0077b5] sm:text-sm sm:leading-6" placeholder="Enter your call message here..."></textarea>
                        </div>
                    </div> --}}

                </div>
                <div class="flex justify-end items-center gap-x-2 py-3 px-4 border-t border-gray-200">
                    <button type="button" id="close-apply-action-main" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none" data-hs-overlay="#hs-medium-modal">
                    Close
                    </button>
                    <button type="button" id="apply-action-main" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent text-white focus:outline-hidden disabled:opacity-50 disabled:pointer-events-none transition-all" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">
                    Save changes
                    </button>
                </div>
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-center mt-4 gap-3">
        <a href="{{route('campaign.create', ['step' => 'sequence', 'cid' => $cid])}}"
            class="block px-10 py-3 text-sm font-medium leading-2 
            text-white transition-colors duration-150 bg-gray-400 
            border border-transparent rounded-tr-lg rounded-bl-lg active:bg-gray-600
            hover:bg-gray-600 focus:outline-none focus:shadow-outline-gray">
            <span class=" flex gap-1 pt-1">
                Back
            </span>
        </a>
        <button type="button" onclick="saveSequence()"
            class="block px-10 py-3 text-sm font-medium leading-2 
            text-white transition-all duration-150 
            border border-transparent rounded-tr-lg rounded-bl-lg focus:outline-none submit-leadgen-sequence" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">
            <span class=" flex gap-1">
                <span class="pt-1">Next</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
            </span>
        </button>
    </div>
</div>
<script>
let dbSequenceType = '{{ $sequenceType }}';
let dbSequenceNode = @json($dbSequenceNode);
let dbSequenceLink = @json($dbSequenceLink);
let nodeDataModel, linkDataModel;

function stripRetiredCallNodes(nodes, links) {
    const list = Array.isArray(nodes) ? nodes : [];
    const callKeys = new Set(list.filter((n) => n && n.value === 'call').map((n) => n.key));
    if (!callKeys.size) {
        return { nodes: list, links: Array.isArray(links) ? links : [] };
    }
    return {
        nodes: list.filter((n) => n.value !== 'call'),
        links: (Array.isArray(links) ? links : []).filter((l) => !callKeys.has(l.from) && !callKeys.has(l.to)),
    };
}

{{-- Commented out - Book a call will be built as a standalone feature --}}
{{-- const callMessage = "Hi @firstName, I'd like to schedule a call to discuss how we can help your business grow. Are you available for a brief conversation this week? I can share some insights about lead generation and business development that might be valuable for @company." --}}

if(dbSequenceType === 'lead_gen' && dbSequenceNode.length > 0 && dbSequenceLink.length > 0){
    const cleaned = stripRetiredCallNodes(dbSequenceNode, dbSequenceLink);
    nodeDataModel = cleaned.nodes
    linkDataModel = cleaned.links
}else {
    nodeDataModel = [
        {key: 0, icon: "\uf007", label: "Send an invite",   type: 'action', value: 'send-invites',  color: "#5A68F7", stroke: "white",  loc: "0 0", hasInviteNote: true, message: "Hey @firstName,\ni will like to join your network.", inviteStatus: '', runday: 0, runStatus: false},
        {key: 1, icon: "\uf017", label: "5 days",           type: 'delay',  value: 5, time: 'days', color: "#F3F4F6", stroke: "black",  loc: "-150 70",     runStatus: false, notAcceptedTime: 1},
        {key: 2, icon: "\uf058", label: "Accepted",         type: 'condition',  value: 'accepted',  color: "#9ca3af", stroke: "white",  loc: "150 70",      runStatus: false},
        {key: 3, icon: "\uf05e", label: "Still not accepted", type: 'condition',value: 'not accepted',  color: "#9ca3af", stroke: "white",  loc: "-150 140",runStatus: false},
        {key: 4, icon: "\uf017", label: "1 hour",           type: 'delay',  value: 1, time: 'hours',color: "#F3F4F6", stroke: "black",  loc: "150 140",     runStatus: false, acceptedTime: 1},
        {key: 5, icon: "\uf2bb", label: "Follow",           type: 'action', value: 'follow',        color: "#5A68F7", stroke: "white",  loc: "-150 210",    runStatus: false, notAcceptedAction: 1},
        {key: 6, icon: "\uf058", label: "Endorse skills",   type: 'action', value: 'endorse',       color: "#5A68F7", stroke: "white",  loc: "150 210", totalSkills: 1, runStatus: false, acceptedAction: 1},
        {key: 7, icon: "\uf017", label: "5 days",           type: 'delay',  value: 5, time: 'days', color: "#F3F4F6", stroke: "black",  loc: "-150 280",    runStatus: false, notAcceptedTime: 2},
        {key: 8, icon: "\uf017", label: "1 hour",           type: 'delay',  value: 1, time: 'hours',color: "#F3F4F6", stroke: "black",  loc: "150 280",     runStatus: false, acceptedTime: 2},
        {key: 9, icon: "\uf05e", label: "Still not accepted", type: 'condition', value: 'not accepted', color: "#9ca3af", stroke: "white",  loc: "-150 350",runStatus: false},
        {key: 10, icon: "\uf27a", label: "Message",         type: 'action', value: 'message',       color: "#5A68F7", stroke: "white",  loc: "150 350", message: '', runStatus: false, acceptedAction: 2},
        {key: 11, icon: "\uf06e", label: "View profile",    type: 'action', value: 'profile-view',  color: "#5A68F7", stroke: "white",  loc: "-150 420",    runStatus: false, notAcceptedAction: 2},
        {key: 12, icon: "\uf017", label: "3 days",          type: 'delay',  value: 3, time: 'days', color: "#F3F4F6", stroke: "black",  loc: "150 420",     runStatus: false, acceptedTime: 3},
        {key: 13, icon: "\uf017", label: "20 days",         type: 'delay',  value: 20, time: 'days',color: "#F3F4F6", stroke: "black",  loc: "-150 490",    runStatus: false, notAcceptedTime: 3},
        {key: 14, icon: "\uf27a", label: "Message",         type: 'action', value: 'message',       color: "#5A68F7", stroke: "white",  loc: "150 490", message: '', runStatus: false, acceptedAction: 3},
        {key: 15, icon: "\uf05e", label: "Still not accepted", type: 'condition', value: 'not accepted',color: "#9ca3af", stroke: "white",  loc: "-150 560",runStatus: false},
        {key: 16, icon: "\uf017", label: "4 days",          type: 'delay',  value: 4, time: 'days', color: "#F3F4F6", stroke: "black",  loc: "150 560",     runStatus: false, acceptedTime: 4},
        {key: 17, icon: "\uf05e", label: "End of sequence", type: 'end',    value: 'end',           color: "#9ca3af", stroke: "white",  loc: "-150 630",    runStatus: false},
        {{-- Commented out - Book a call will be built as a standalone feature --}}
        {{-- {key: 18, icon: "\uf133", label: "Book a call",     type: 'action', value: 'call',          color: "#5A68F7", stroke: "white",  loc: "150 630", message: callMessage, runStatus: false, acceptedAction: 4},
        {key: 19, icon: "\uf017", label: "1 days",          type: 'delay',  value: 1, time: 'days', color: "#F3F4F6", stroke: "black",  loc: "150 700",     runStatus: false},
        {key: 20, icon: "\uf05e", label: "End of sequence", type: 'end',    value: 'end',           color: "#9ca3af", stroke: "white",  loc: "150 770",     runStatus: false}, --}}
        {key: 18, icon: "\uf05e", label: "End of sequence", type: 'end',    value: 'end',           color: "#9ca3af", stroke: "white",  loc: "150 630",     runStatus: false},
    ]
    linkDataModel = [
        {from: 0, to: 1, fromSpot: "Left", toSpot: "Top"},
        {from: 0, to: 2, fromSpot: "Right", toSpot: "Top"},
        {from: 1, to: 3, fromSpot: "Bottom", toSpot: "Top"},
        {from: 2, to: 4, fromSpot: "Bottom", toSpot: "Top"},
        {from: 3, to: 5, fromSpot: "Bottom", toSpot: "Top"},
        {from: 4, to: 6, fromSpot: "Bottom", toSpot: "Top"},
        {from: 5, to: 7, fromSpot: "Bottom", toSpot: "Top"},
        {from: 6, to: 8, fromSpot: "Bottom", toSpot: "Top"},
        {from: 7, to: 9, fromSpot: "Bottom", toSpot: "Top"},
        {from: 8, to: 10, fromSpot: "Bottom", toSpot: "Top"},
        {from: 9, to: 11, fromSpot: "Bottom", toSpot: "Top"},
        {from: 10, to: 12, fromSpot: "Bottom", toSpot: "Top"},
        {from: 11, to: 13, fromSpot: "Bottom", toSpot: "Top"},
        {from: 12, to: 14, fromSpot: "Bottom", toSpot: "Top"},
        {from: 13, to: 15, fromSpot: "Bottom", toSpot: "Top"},
        {from: 14, to: 16, fromSpot: "Bottom", toSpot: "Top"},
        {from: 15, to: 17, fromSpot: "Bottom", toSpot: "Top"},
        {from: 16, to: 18, fromSpot: "Bottom", toSpot: "Top"},
        {{-- Commented out - Book a call will be built as a standalone feature --}}
        {{-- {from: 16, to: 18, fromSpot: "Bottom", toSpot: "Top"},
        {from: 18, to: 19, fromSpot: "Bottom", toSpot: "Top"},
        {from: 19, to: 20, fromSpot: "Bottom", toSpot: "Top"}, --}}
    ]
}

let nodeItem, nodeKey, hasInviteNote;
let applyActionBtn = document.querySelector('#applyAction')
let delayFields = document.querySelector('#delay-fields')
let actionField = document.querySelector('#action-field')
let inviteMessageFields = document.querySelector('#send-invites-fields')
let sendMessageFields = document.querySelector('#send-message-fields')
let endorseSkillFields = document.querySelector('#endorse-fields')

let timeNumber = document.querySelector('#time-number')
let timeType = document.querySelector('#time-type')
let inviteNote = document.querySelector('#invite-note'),
    inviteMessage = document.querySelector('#invite-message'),
    sendMessage = document.querySelector('#send-message'),
    totalEndorseSkill = document.querySelector('#total-endorse-skill')

{{-- Commented out - Book a call will be built as a standalone feature --}}
{{-- let callMessageSpace = document.querySelector('.call-message-space'),
    callMessageTextField = document.querySelector('#call-message'),
    callFields = document.querySelector('#book-call-fields'), --}}
let reviewTimeContainer = document.querySelector('#review-time-container'),
    reviewTimeInput = document.querySelector('#review-time');

let applyActionMain = document.querySelector('#apply-action-main'),
    closeApplyActionMain = document.querySelector('#close-apply-action-main');

let modalTitle = document.querySelector('.modal-title')

const initLeadGen = () => {
    const diagram = new go.Diagram("myLeadgenDiagramDiv");

    // Explicitly load a font.  Only do this once, not each time you load a model.
    const awesome = new FontFace("awesome",
        "url(https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/fonts/fontawesome-webfont.ttf)");
    document.fonts.add(awesome);
    // Wait for the Font Awesome font to load before actually loading the diagram.
    awesome.load().then(() => load());

    let nodeDataArray = []
    let linkDataArray = []

    const load = () => {
        diagram.nodeTemplate = new go.Node("Auto", { locationSpot: go.Spot.Center })
            .bind("location", "loc", go.Point.parse)
            .add(
                new go.Shape("Rectangle", {border: 0}).bind("fill", "color"), 
                new go.TextBlock({ margin: 8})
                    .bind("text")
                    .bind("stroke")
                    .bind("font"),
            );
        diagram.linkTemplate = new go.Link({
                routing: go.Routing.AvoidsNodes,
                corner: 10    // rounded corners
            })
            .bind("fromSpot", "fromSpot", go.Spot.parse)
            .bind("toSpot", "toSpot", go.Spot.parse)
            .add(
                new go.Shape()
            );

        // Diagram node event listener
        diagram.addDiagramListener('ObjectSingleClicked', function(e){
            nodeItem = e.subject.Hs.si
            nodeKey = nodeItem.key
            actionField.value = nodeItem.type

            for(let elem of document.querySelectorAll('.leadgen-sequence-fields')){
                elem.style.display = 'none'
            }
            applyActionBtn.style.display = 'none'

            if(nodeItem.type == 'delay'){
                document.querySelector('.lead-gen-modal-btn').click()

                timeNumber.value = nodeItem.value
                timeType.value = nodeItem.time
                delayFields.style.display = 'block'
                // applyActionBtn.style.display = 'block'  

                modalTitle.innerHTML = 'Delay'                 
            }else if(nodeItem.type == 'action' && nodeItem.value == 'send-invites'){
                document.querySelector('.lead-gen-modal-btn').click()

                inviteMessage.value = nodeDataModel[nodeKey].message
                inviteNote.checked = nodeDataModel[nodeKey].hasInviteNote
                inviteMessageFields.style.display = 'block'
                // applyActionBtn.style.display = 'block'

                modalTitle.innerHTML = 'Send Invites'
            }else if(nodeItem.type == 'action' && nodeItem.value == 'message'){
                document.querySelector('.lead-gen-modal-btn').click()

                sendMessage.value = nodeDataModel[nodeKey].message
                sendMessageFields.style.display = 'block'
                // applyActionBtn.style.display = 'block'

                modalTitle.innerHTML = 'Message'
            }else if(nodeItem.type == 'action' && nodeItem.value == 'endorse'){
                document.querySelector('.lead-gen-modal-btn').click()

                totalEndorseSkill.value = nodeDataModel[nodeKey].totalSkills
                endorseSkillFields.style.display = 'block'
                // applyActionBtn.style.display = 'block'

                modalTitle.innerHTML = 'Endorse'
            }
            {{-- Commented out - Book a call will be built as a standalone feature --}}
            {{-- else if(nodeItem.type == 'action' && nodeItem.value == 'call'){
                document.querySelector('.lead-gen-modal-btn').click()

                callMessageTextField.value = nodeDataModel[nodeKey].message
                document.querySelector('#use-ai-paraphrase').checked = nodeDataModel[nodeKey].paraphrase_user_message || false
                
                // Set AI mode settings
                const aiMode = nodeDataModel[nodeKey].ai_mode || 'auto';
                document.querySelector(`input[name="ai_mode"][value="${aiMode}"]`).checked = true;
                
                if (aiMode === 'review') {
                    reviewTimeContainer.style.display = 'block';
                    const savedReviewTime = nodeDataModel[nodeKey].review_time || 30;
                    reviewTimeInput.value = savedReviewTime;
                    console.log('🔍 Loading review mode settings:', {
                        savedReviewTime: savedReviewTime,
                        inputValue: reviewTimeInput.value
                    });
                } else {
                    reviewTimeContainer.style.display = 'none';
                }
                
                callMessageSpace.style.display = 'block'
                callFields.style.display = 'block'
                modalTitle.innerHTML = 'Book a Call'
            } --}}
        })
        setNodeLinkArray()
    }

    /**
     * Listen to action event and Update node model
     **/
    applyActionBtn.addEventListener('click', () => {
        if(actionField.value == 'delay'){
            nodeDataModel[nodeKey].label = timeNumber.value == 0 ? 'No delay' : `${timeNumber.value} ${timeType.value}`
            nodeDataModel[nodeKey].value = timeNumber.value
            nodeDataModel[nodeKey].time = timeType.value
            delayFields.style.display = 'none'
        }else if(nodeItem.type == 'action' && nodeItem.value == 'send-invites'){
            nodeDataModel[nodeKey].message = inviteMessage.value
            nodeDataModel[nodeKey].hasInviteNote = inviteMessage.disabled
            inviteMessageFields.style.display = 'none'
        }else if(nodeItem.type == 'action' && nodeItem.value == 'message'){
            nodeDataModel[nodeKey].message = sendMessage.value
            sendMessageFields.style.display = 'none'
        }else if(nodeItem.type == 'action' && nodeItem.value == 'endorse'){
            nodeDataModel[nodeKey].totalSkills = totalEndorseSkill.value
            endorseSkillFields.style.display = 'none'
        }
        setNodeLinkArray()
        applyActionBtn.style.display = 'none'
    })

    applyActionMain.addEventListener('click', () => {
        if(actionField.value == 'delay'){
            nodeDataModel[nodeKey].label = timeNumber.value == 0 ? 'No delay' : `${timeNumber.value} ${timeType.value}`
            nodeDataModel[nodeKey].value = timeNumber.value
            nodeDataModel[nodeKey].time = timeType.value
            delayFields.style.display = 'none'
        }else if(nodeItem.type == 'action' && nodeItem.value == 'send-invites'){
            nodeDataModel[nodeKey].message = inviteMessage.value
            nodeDataModel[nodeKey].hasInviteNote = inviteMessage.disabled
            inviteMessageFields.style.display = 'none'
        }else if(nodeItem.type == 'action' && nodeItem.value == 'message'){
            nodeDataModel[nodeKey].message = sendMessage.value
            sendMessageFields.style.display = 'none'
        }else if(nodeItem.type == 'action' && nodeItem.value == 'endorse'){
            nodeDataModel[nodeKey].totalSkills = totalEndorseSkill.value
            endorseSkillFields.style.display = 'none'
        }
        {{-- Commented out - Book a call will be built as a standalone feature --}}
        {{-- else if(nodeItem.type == 'action' && nodeItem.value == 'call'){
            nodeDataModel[nodeKey].message = callMessageTextField.value
            nodeDataModel[nodeKey].paraphrase_user_message = document.querySelector('#use-ai-paraphrase').checked
            
            // Save AI mode settings
            const selectedAiMode = document.querySelector('input[name="ai_mode"]:checked').value;
            const reviewTimeValue = reviewTimeInput.value;
            const parsedReviewTime = selectedAiMode === 'review' ? parseInt(reviewTimeValue, 10) : null;
            console.log('🔍 Saving AI mode settings:', {
                selectedAiMode: selectedAiMode,
                reviewTimeInputValue: reviewTimeValue,
                parsedReviewTime: parsedReviewTime,
                isValidNumber: !isNaN(parsedReviewTime) && parsedReviewTime > 0
            });
            
            nodeDataModel[nodeKey].ai_mode = selectedAiMode;
            nodeDataModel[nodeKey].review_time = (parsedReviewTime && parsedReviewTime > 0) ? parsedReviewTime : null;
            
            console.log('💾 Saved to nodeDataModel:', {
                ai_mode: nodeDataModel[nodeKey].ai_mode,
                review_time: nodeDataModel[nodeKey].review_time
            });
            
            callMessageSpace.style.display = 'none'
            callFields.style.display = 'none'
        } --}}
        setNodeLinkArray()
        closeApplyActionMain.click()
    })

    /**
     * Listen to invite note status change and update node model
     **/
    inviteNote.addEventListener('change', (e) => {
        if(e.target.checked){
            inviteMessage.disabled = true
        }else {
            inviteMessage.disabled = false
        }
    })

    /**
     * Listen to AI mode selection and show/hide review time input
     **/
    document.querySelectorAll('input[name="ai_mode"]').forEach(radio => {
        radio.addEventListener('change', (e) => {
            if (e.target.value === 'review') {
                reviewTimeContainer.style.display = 'block';
            } else {
                reviewTimeContainer.style.display = 'none';
            }
        });
    });

    /**
     * Set node, link array values
     **/
    const setNodeLinkArray = () => {
        nodeDataArray = []
        for(let item of nodeDataModel) {
            if(item.type === 'action' && item.label === 'View profile')
                item.icon = "\uf06e"
            else if(item.type === 'action' && item.label === 'Follow')
                item.icon = "\uf2bb"
            else if(item.type === 'action' && item.label === 'Like a post')
                item.icon = "\uf164"
            else if(item.type === 'action' && item.label === 'Send an invite')
                item.icon = "\uf007"
            else if(item.type === 'action' && item.label === 'Endorse skills')
                item.icon = "\uf058"
            else if(item.type === 'action' && item.label === 'Message')
                item.icon = "\uf27a"
            else if(item.type === 'condition' && item.label === 'Accepted')
                item.icon = "\uf058"
            else if(item.type === 'condition' && item.label === 'Still not accepted')
                item.icon = "\uf05e"
            else if(item.type === 'delay')
                item.icon = "\uf017"
            else if(item.type === 'end')
                item.icon = "\uf05e"

            nodeDataArray.push({
                key: item.key, 
                text: `${item.icon} ${item.label}`,
                font: "12pt awesome", 
                color: item.color, 
                stroke: item.stroke, 
                loc: item.loc,
                type: item.type,
                value: item.value,
                time: item?.time,
            })
        }
        linkDataArray = []
        linkDataArray = linkDataModel;
        diagram.model = new go.GraphLinksModel(nodeDataArray, linkDataArray)
    }
}
initLeadGen()

/**
 * Add variable to message.
 * @param {string} mv
 */
const addVariableToMessage = (mv) => {
    let cursorPos, textBefore, textAfter;

    if(nodeItem.value == 'send-invites'){
        cursorPos = inviteMessage.selectionStart
        textBefore = inviteMessage.value.substring(0,  cursorPos)
        textAfter  = inviteMessage.value.substring(cursorPos, inviteMessage.value.length)
        inviteMessage.value = textBefore + mv + textAfter
    }else if(nodeItem.value == 'message'){
        cursorPos = sendMessage.selectionStart
        textBefore = sendMessage.value.substring(0,  cursorPos)
        textAfter  = sendMessage.value.substring(cursorPos, sendMessage.value.length)
        sendMessage.value = textBefore + mv + textAfter
    }
    {{-- Commented out - Book a call will be built as a standalone feature --}}
    {{-- else if(nodeItem.value == 'call'){
        cursorPos = callMessageTextField.selectionStart
        textBefore = callMessageTextField.value.substring(0,  cursorPos)
        textAfter = callMessageTextField.value.substring(cursorPos, callMessageTextField.value.length)
        callMessageTextField.value = textBefore + mv + textAfter
    } --}}
}

/**
 * Hide all fields.
 */
const hideAllFields = () => {
    inviteMessageFields.style.display = 'none'
    delayFields.style.display = 'none'
    sendMessageFields.style.display = 'none'
    endorseSkillFields.style.display = 'none'
    applyActionBtn.style.display = 'none'
}

/**
 * Save sequence data
 **/
const saveSequence = async () => {
    let formData = new FormData();
    formData.append('sequence_type', 'lead_gen');
    formData.append('node_model', JSON.stringify(nodeDataModel));
    formData.append('link_model', JSON.stringify(linkDataModel));

    let searchParams = new URLSearchParams(window.location.search);
    let sequenceStep = searchParams.get("step"),
        campaignId = searchParams.get("cid");
    let submitSequenceBtn = document.querySelector('.submit-leadgen-sequence')
    submitSequenceBtn.disabled = true

    await fetch(`/sequence/store?step=${sequenceStep}&cid=${campaignId}`, {
        headers: {'X-CSRF-TOKEN': "{{ csrf_token() }}"},
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        submitSequenceBtn.disabled = false
        if(res.status === 201){
            window.location = res.redirect
        }
    })
}
</script>
