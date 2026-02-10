<div class="mt-10">
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Call Campaigns</h3>
            <p class="text-sm text-gray-500">Send a 3-step LinkedIn message flow with your calendar link.</p>
        </div>
    </div>

    <div class="mb-6 bg-white border border-gray-200 rounded-lg shadow-sm p-4">
        <form method="post" action="{{ route('calls.campaigns.store') }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="call-campaign-name" class="block mb-2 text-sm font-medium text-gray-900">Campaign name</label>
                    <input id="call-campaign-name" type="text" name="name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="My Call Campaign" required />
                </div>
                <div>
                    <label for="call-campaign-audience" class="block mb-2 text-sm font-medium text-gray-900">Audience list</label>
                    <select id="call-campaign-audience" name="audience_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                        <option value="">Select audience</option>
                        @foreach($audiences as $audience)
                            <option value="{{ $audience->audience_id }}">{{ $audience->audience_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                <div>
                    <label for="call-campaign-message-one" class="block mb-2 text-sm font-medium text-gray-900">Message 1 (Opening)</label>
                    <textarea id="call-campaign-message-one" name="message_one" rows="5" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="Hi {firstName}, ..." required></textarea>
                </div>
                <div>
                    <label for="call-campaign-message-two" class="block mb-2 text-sm font-medium text-gray-900">Message 2 (Calendar link)</label>
                    <textarea id="call-campaign-message-two" name="message_two" rows="5" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="Here is my calendar link: {calendarLink}" required></textarea>
                    <p class="mt-2 text-xs text-gray-500">We automatically add {calendarLink} if you don’t include it.</p>
                </div>
                <div>
                    <label for="call-campaign-message-three" class="block mb-2 text-sm font-medium text-gray-900">Message 3 (Follow-up)</label>
                    <textarea id="call-campaign-message-three" name="message_three" rows="5" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="Just following up..." required></textarea>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div>
                    <label for="call-campaign-delay-two" class="block mb-2 text-sm font-medium text-gray-900">Delay before Message 2 (minutes)</label>
                    <input id="call-campaign-delay-two" type="number" name="delay_two_minutes" min="0" value="60" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required />
                </div>
                <div>
                    <label for="call-campaign-delay-three" class="block mb-2 text-sm font-medium text-gray-900">Delay before Message 3 (minutes)</label>
                    <input id="call-campaign-delay-three" type="number" name="delay_three_minutes" min="0" value="1440" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required />
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="py-2 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-hidden focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">
                    Create Call Campaign
                </button>
            </div>
        </form>
    </div>

    <div class="w-full overflow-hidden rounded-lg">
        <div class="w-full overflow-x-auto">
            <div class="grid grid-cols-12 p-3 mb-3 bg-white border border-gray-200 rounded-lg shadow-sm font-semibold px-3 text-sm">
                <div class="col-span-3">Campaign</div>
                <div class="col-span-2">Audience</div>
                <div class="col-span-2">Status</div>
                <div class="col-span-2">Leads</div>
                <div class="col-span-2">Messages Sent</div>
                <div class="col-span-1"></div>
            </div>
            <div>
                @forelse($callCampaigns as $campaign)
                    <div class="grid grid-cols-12 p-3 px-3 mb-2 bg-white hover:bg-indigo-100 rounded-lg shadow-sm font-normal text-gray-600 text-sm">
                        <div class="col-span-3">
                            <div class="font-semibold text-gray-800">{{ $campaign->name }}</div>
                            <div class="text-xs text-gray-400">{{ $campaign->created_at->format('M j, Y') }}</div>
                        </div>
                        <div class="col-span-2">{{ $campaign->audience?->audience_name ?? 'Audience' }}</div>
                        <div class="col-span-2 capitalize">{{ str_replace('_', ' ', $campaign->status) }}</div>
                        <div class="col-span-2">{{ $campaign->leads_count }}</div>
                        <div class="col-span-2">
                            {{ $campaign->messages_sent_count }} / {{ $campaign->leads_count * 3 }}
                        </div>
                        <div class="col-span-1">
                            <div class="flex flex-col gap-2">
                                @if($campaign->status === 'active')
                                    <form method="post" action="{{ route('calls.campaigns.status', $campaign->id) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="paused" />
                                        <button type="submit" class="text-xs text-red-600 hover:text-red-800">Pause</button>
                                    </form>
                                @elseif($campaign->status === 'paused')
                                    <form method="post" action="{{ route('calls.campaigns.status', $campaign->id) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="active" />
                                        <button type="submit" class="text-xs text-green-600 hover:text-green-800">Resume</button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400">Done</span>
                                @endif
                                <form method="post" action="{{ route('calls.campaigns.delete', $campaign->id) }}" onsubmit="return confirm('Delete this call campaign? This cannot be undone.');">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="text-xs text-gray-500 hover:text-gray-700">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-sm text-gray-500 bg-white border border-gray-200 rounded-lg">
                        No call campaigns yet. Create one to start sending your 3-step call flow.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
