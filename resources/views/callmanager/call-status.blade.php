<div class="mt-3">
    <div class="flex items-center justify-between mb-2">
        <div class="text-xs text-gray-500">
            Call Status entries are separate from Call Campaigns.
        </div>
        <form method="post" action="{{ route('calls.clear') }}" onsubmit="return confirm('Clear all call status entries? This cannot be undone.');">
            @csrf
            @method('delete')
            <button type="submit" class="px-3 py-1 text-xs font-medium text-white bg-red-600 rounded-md hover:bg-red-700 focus:outline-hidden focus:ring-2 focus:ring-red-500">
                Clear Call Status
            </button>
        </form>
    </div>
    <div class="w-full overflow-hidden rounded-lg">
        <div class="w-full overflow-x-auto">
            <div class="grid grid-cols-12 p-3 mb-3 bg-white border border-gray-200 rounded-lg shadow-sm font-semibold px-3 text-sm">
                <div class="col-span-2">Recipient</div>
                <div class="col-span-2">Profile</div>
                <div class="col-span-2">Sequence</div>
                <div class="col-span-2">Call Status</div>
                <div class="col-span-3">Scheduled Time</div>
                <div class="col-span-1"></div>
            </div>
            <div>
            @foreach($callStatus as $item)
                <div class="grid grid-cols-12 p-3 px-3 mb-2 bg-white hover:bg-indigo-100 rounded-lg shadow-sm font-normal cursor-pointer text-gray-600 text-sm">
                    <div class="col-span-2">{{$item->recipient}}</div>
                    <div class="col-span-2">{{$item->profile}}</div>
                    <div class="col-span-2">{{$item->sequence}}</div>
                    <div class="col-span-2 capitalize">{{str_replace('_',' ',$item->call_status)}}</div>
                    <div class="col-span-3">
                        @if($item->scheduled_time)
                            {{ \Carbon\Carbon::parse($item->scheduled_time)->format('M j, Y g:i A') }}
                        @else
                            <span class="text-gray-400">Not scheduled</span>
                        @endif
                    </div>
                    <div class="col-span-1">
                        <div class="flex gap-3">
                            <a href="{{ route('calls.show', $item->id) }}" 
                               class="text-orange-600 hover:text-orange-800" 
                               title="View Details">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                                    <path d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
                                    <path fill-rule="evenodd" d="M1.323 11.447C2.811 6.976 7.028 3.75 12.001 3.75c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113-1.487 4.471-5.705 7.697-10.677 7.697-4.97 0-9.186-3.223-10.675-7.69a1.762 1.762 0 010-1.113zM17.25 12a5.25 5.25 0 11-10.5 0 5.25 5.25 0 0110.5 0z" clip-rule="evenodd" />
                                </svg>
                            </a>
                            @if($item->pending_message && $item->call_status == 'pending_review' && $item->scheduled_send_at)
                            <button
                            data-id="{{$item->id}}"
                            data-pending-message="{{$item->pending_message}}"
                            data-scheduled-send-at="{{$item->scheduled_send_at}}"
                            class="edit-pending-message-modal text-green-600 hover:text-green-800"
                            title="Edit Pending Message">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                                    <path d="M21.731 2.269a2.625 2.625 0 00-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 000-3.712zM19.513 8.199l-3.712-3.712-8.4 8.4a5.25 5.25 0 00-1.32 2.214l-.8 2.685a.75.75 0 00.933.933l2.685-.8a5.25 5.25 0 002.214-1.32l8.4-8.4z" />
                                    <path d="M5.25 5.25a3 3 0 00-3 3v10.5a3 3 0 003 3h10.5a3 3 0 003-3V13.5a.75.75 0 00-1.5 0v5.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5V8.25a1.5 1.5 0 011.5-1.5h5.25a.75.75 0 000-1.5H5.25z" />
                                </svg>
                            </button>
                            @endif
                            <button
                            data-id="{{$item->id}}"
                            data-status="{{$item->call_status}}"
                            class="update-status-modal text-indigo-600"
                            title="Update Status">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                                <path d="M21.731 2.269a2.625 2.625 0 00-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 000-3.712zM19.513 8.199l-3.712-3.712-8.4 8.4a5.25 5.25 0 00-1.32 2.214l-.8 2.685a.75.75 0 00.933.933l2.685-.8a5.25 5.25 0 002.214-1.32l8.4-8.4z" />
                                <path d="M5.25 5.25a3 3 0 00-3 3v10.5a3 3 0 003 3h10.5a3 3 0 003-3V13.5a.75.75 0 00-1.5 0v5.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5V8.25a1.5 1.5 0 011.5-1.5h5.25a.75.75 0 000-1.5H5.25z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
            </div>
            @if(count($callStatus)>0)
            {{ $callStatus->links() }}
            @endif
        </div>
    </div>
</div>

<button type="button" id="update-status-btn" style="display: none;" class="py-3 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-hidden focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none" aria-haspopup="dialog" aria-expanded="false" aria-controls="hs-basic-modal" data-hs-overlay="#hs-basic-modal">
  Open modal
</button>

<button type="button" id="edit-pending-message-btn" style="display: none;" class="py-3 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-green-600 text-white hover:bg-green-700 focus:outline-hidden focus:bg-green-700 disabled:opacity-50 disabled:pointer-events-none" aria-haspopup="dialog" aria-expanded="false" aria-controls="hs-pending-message-modal" data-hs-overlay="#hs-pending-message-modal">
  Open pending message modal
</button>

<div id="hs-basic-modal" class="hs-overlay hs-overlay-open:opacity-100 hs-overlay-open:duration-500 hidden size-full fixed top-0 start-0 z-80 opacity-0 overflow-x-hidden transition-all overflow-y-auto pointer-events-none" role="dialog" tabindex="-1" aria-labelledby="hs-basic-modal-label">
    <div class="sm:max-w-lg sm:w-full m-3 sm:mx-auto">
        <div class="flex flex-col bg-white border border-gray-200 shadow-2xs rounded-xl pointer-events-auto">
            <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200">
                <h3 id="hs-basic-modal-label" class="font-bold text-gray-800">
                Call Status
                </h3>
                <button type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-full border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 focus:outline-hidden focus:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none" aria-label="Close" data-hs-overlay="#hs-basic-modal">
                <span class="sr-only">Close</span>
                <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6 6 18"></path>
                    <path d="m6 6 12 12"></path>
                </svg>
                </button>
            </div>
            <form method="post" action="{{route('calls.update')}}">
                @csrf
                @method('put')
                <div class="p-4 overflow-y-auto">
                    <div>
                        <input type="hidden" name="call_status_id" class="call-status-id" />
                        <label for="call-status" class="block mb-2 text-sm font-medium text-gray-900">Call status</label>
                        <select id="call-status" name="call_status" class="call-status bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required="">
                            <option value="made">Made</option>
                            <option value="scheduled">Scheduled</option>
                            <option value="suggested">Suggested</option>
                            <option value="got_email">Got email</option>
                            <option value="got_phone">Got phone</option>
                            <option value="not_show">No show</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end items-center gap-x-2 py-3 px-4 border-t border-gray-200">
                    <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-2xs hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none" data-hs-overlay="#hs-basic-modal">
                    Close
                    </button>
                    <button type="submit" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-hidden focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">
                    Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Pending Message Modal -->
<div id="hs-pending-message-modal" class="hs-overlay hs-overlay-open:opacity-100 hs-overlay-open:duration-500 hidden size-full fixed top-0 start-0 z-80 opacity-0 overflow-x-hidden transition-all overflow-y-auto pointer-events-none" role="dialog" tabindex="-1" aria-labelledby="hs-pending-message-modal-label">
    <div class="sm:max-w-2xl sm:w-full m-3 sm:mx-auto">
        <div class="flex flex-col bg-white border border-gray-200 shadow-2xs rounded-xl pointer-events-auto">
            <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200">
                <h3 id="hs-pending-message-modal-label" class="font-bold text-gray-800">
                Edit Pending Message
                </h3>
                <button type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-full border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 focus:outline-hidden focus:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none" aria-label="Close" data-hs-overlay="#hs-pending-message-modal">
                <span class="sr-only">Close</span>
                <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6 6 18"></path>
                    <path d="m6 6 12 12"></path>
                </svg>
                </button>
            </div>
            <form method="post" action="{{route('calls.update-pending-message')}}">
                @csrf
                @method('put')
                <div class="p-4 overflow-y-auto">
                    <div class="mb-4">
                        <input type="hidden" name="call_id" class="pending-call-id" />
                        <label for="pending-message" class="block mb-2 text-sm font-medium text-gray-900">Pending Message</label>
                        <textarea id="pending-message" name="pending_message" rows="6" class="pending-message bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="Enter your pending message here..."></textarea>
                    </div>
                    <div class="mb-4">
                        <label for="scheduled-send-at" class="block mb-2 text-sm font-medium text-gray-900">Scheduled Send Time</label>
                        <input type="text" id="scheduled-send-at" class="scheduled-send-at bg-gray-100 border border-gray-300 text-gray-600 text-sm rounded-lg block w-full p-2.5" readonly />
                    </div>
                </div>
                <div class="flex justify-end items-center gap-x-2 py-3 px-4 border-t border-gray-200">
                    <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-2xs hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none" data-hs-overlay="#hs-pending-message-modal">
                    Close
                    </button>
                    <button type="submit" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-green-600 text-white hover:bg-green-700 focus:outline-hidden focus:bg-green-700 disabled:opacity-50 disabled:pointer-events-none">
                    Update Message
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$('.update-status-modal').click(function(){
    $('#update-status-btn').click()
    $('.call-status').val($(this).data('status'))
    $('.call-status-id').val($(this).data('id'))
})

$('.edit-pending-message-modal').click(function(){
    $('#edit-pending-message-btn').click()
    $('.pending-call-id').val($(this).data('id'))
    $('.pending-message').val($(this).data('pending-message'))
    
    // Format datetime for display (read-only)
    const scheduledSendAt = $(this).data('scheduled-send-at')
    if (scheduledSendAt) {
        const date = new Date(scheduledSendAt)
        const formattedDate = date.toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        })
        $('.scheduled-send-at').val(formattedDate)
    } else {
        $('.scheduled-send-at').val('Not scheduled')
    }
})

</script>
