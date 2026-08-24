@extends('layout.auth')

@section('content')
<div class="flex justify-between">
    <div class="pt-3">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
                <li class="inline-flex items-center">
                    <a href="{{route('leads.list')}}" class="inline-flex gap-2 items-center text-sm font-medium text-gray-700 hover:text-[#0077b5]">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                        </svg>
                        Lead list
                    </a>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <svg class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                        </svg>
                        <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2">{{$leadlist->name ?? 'N/A'}}</span>
                        <input type="hidden" value="{{$leadlist->name ?? ''}}" id="list-name">
                    </div>
                </li>
            </ol>
        </nav>
    </div>
    <div class="flex gap-2">
        @if(request()->query('src') == 'aud')
        <form action="{{route('leads.show', $listId ?? request()->route('listId'))}}" method="get" class="flex items-center">
            <input type="hidden" name="src" value="{{request()->query('src')}}">
            <select name="email_filter" id="email-filter" onchange="this.form.submit()" class="block w-full px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-[#0077b5] focus:border-[#0077b5]" style="min-width: 180px;">
                <option value="all" {{($emailFilter ?? 'all') == 'all' ? 'selected' : ''}}>All Emails</option>
                <option value="with_email" {{($emailFilter ?? 'all') == 'with_email' ? 'selected' : ''}}>With Email</option>
                <option value="without_email" {{($emailFilter ?? 'all') == 'without_email' ? 'selected' : ''}}>Without Email</option>
                <option value="not_found" {{($emailFilter ?? 'all') == 'not_found' ? 'selected' : ''}}>Not Found</option>
                <option value="not_fetched" {{($emailFilter ?? 'all') == 'not_fetched' ? 'selected' : ''}}>Not Yet Fetched</option>
                <option value="pending" {{($emailFilter ?? 'all') == 'pending' ? 'selected' : ''}}>Pending</option>
            </select>
        </form>
        @endif
        <form action="{{route('leads.search_leads')}}" method="get">
            <label class="block text-sm">
                <div class="relative text-gray-500 focus-within:text-[#0077b5]">
                <input type="hidden" name="src" id="list-src" value="{{request()->query('src')}}">
                    @if(count($leads)>0)
                    <input type="hidden" name="list_id" id="list-hash" value="{{$leads[0]->list_hash}}">
                    @else
                    <input type="hidden" name="list_id" id="list-hash" value="">
                    @endif
                    <input class="block w-full pr-20 mt-1 text-sm text-black 
                    focus:border-[#0077b5] focus:outline-none rounded
                    focus:shadow-outline-[#0077b5] 
                    form-input"
                    name="search"
                    placeholder="Search headline, location" 
                    value="{{request()->query('search')}}"/>
                    <button 
                    type="submit"
                    class="absolute inset-y-0 right-0 px-4 text-sm 
                    font-medium leading-5 text-white transition-all 
                    duration-150 border border-transparent 
                    rounded-r-md focus:outline-none" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                            <path fill-rule="evenodd" d="M10.5 3.75a6.75 6.75 0 100 13.5 6.75 6.75 0 000-13.5zM2.25 10.5a8.25 8.25 0 1114.59 5.28l4.69 4.69a.75.75 0 11-1.06 1.06l-4.69-4.69A8.25 8.25 0 012.25 10.5z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        </form>
        <!-- <a 
        href="{{route('leads.list')}}"
        class="block px-4 py-2 text-sm font-medium leading-2 
        text-white transition-all duration-150 
        border border-transparent rounded-md focus:outline-none
        flex gap-2" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            <span class="pt-1">Back</span>
        </a> -->
    </div>
</div>

<div class="mt-2 flex gap-2 check-actions px-4" style="display: none;">
    <button type="button"
    class="block px-4 py-2 text-sm font-medium leading-2 
    text-white transition-all duration-150 
    border border-transparent rounded-md focus:outline-none
    flex gap-1 export" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
            <path fill-rule="evenodd" d="M7.5 3.75A1.5 1.5 0 0 0 6 5.25v13.5a1.5 1.5 0 0 0 1.5 1.5h6a1.5 1.5 0 0 0 1.5-1.5V15a.75.75 0 0 1 1.5 0v3.75a3 3 0 0 1-3 3h-6a3 3 0 0 1-3-3V5.25a3 3 0 0 1 3-3h6a3 3 0 0 1 3 3V9A.75.75 0 0 1 15 9V5.25a1.5 1.5 0 0 0-1.5-1.5h-6Zm10.72 4.72a.75.75 0 0 1 1.06 0l3 3a.75.75 0 0 1 0 1.06l-3 3a.75.75 0 1 1-1.06-1.06l1.72-1.72H9a.75.75 0 0 1 0-1.5h10.94l-1.72-1.72a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
        </svg>
        <span class="pt-1">Export</span>
    </button>
    <form action="" method="post" id="delete-form">
        @csrf
        @method('DELETE')
        <button type="submit"
        class="block px-4 py-2 text-sm font-medium leading-2 
        text-white transition-colors duration-150 bg-red-600 
        border border-transparent rounded-md active:bg-red-600
        hover:bg-red-700 focus:outline-none focus:shadow-outline-red
        flex gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                <path fill-rule="evenodd" d="M16.5 4.478v.227a48.816 48.816 0 013.878.512.75.75 0 11-.256 1.478l-.209-.035-1.005 13.07a3 3 0 01-2.991 2.77H8.084a3 3 0 01-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 01-.256-1.478A48.567 48.567 0 017.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 013.369 0c1.603.051 2.815 1.387 2.815 2.951zm-6.136-1.452a51.196 51.196 0 013.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 00-6 0v-.113c0-.794.609-1.428 1.364-1.452zm-.355 5.945a.75.75 0 10-1.5.058l.347 9a.75.75 0 101.499-.058l-.346-9zm5.48.058a.75.75 0 10-1.498-.058l-.347 9a.75.75 0 001.5.058l.345-9z" clip-rule="evenodd" />
            </svg>
            <span class="pt-1">Remove leads</span>
        </button>
    </form>
</div>

@if(request()->query('src') == 'aud')
<div class="mt-2 px-4">
    <div id="daily-limit-info" class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm">
        <div class="flex items-center justify-between">
            <span class="text-gray-700">
                <strong>Daily Email Scraping Limit:</strong> 
                <span id="daily-limit-used">0</span> / <span id="daily-limit-total">{{ config('services.email_scraping.daily_limit_per_user', 100) }}</span> profiles
            </span>
            <span id="daily-limit-status" class="text-gray-600">Loading...</span>
        </div>
        <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
            <div id="daily-limit-progress" class="bg-blue-600 h-2 rounded-full" style="width: 0%"></div>
        </div>
    </div>
</div>
@endif

<div class="mt-3 px-4">
    <div class="w-full overflow-hidden rounded-lg">
        <div class="w-full overflow-x-auto">
            <div class="grid grid-cols-12 p-3 mb-3 bg-white border border-gray-200 rounded-lg shadow-sm font-semibold px-3 text-sm">
                <div class="col-span-2 flex gap-2">
                    <div class="flex items-center">
                        <input id="check-all" type="checkbox" value="" class="checkbox-main shrink-0 mt-0.5 border-gray-200 rounded-sm focus:ring-[#0077b5] disabled:opacity-50 disabled:pointer-events-none" style="accent-color: #0077b5;">
                    </div>
                    Name
                </div>
                <div class="col-span-3">Email</div>
                <div class="col-span-2">Headline</div>
                <div class="col-span-2">Location</div>
                <div class="col-span-2">Date</div>
                <div class="col-span-1"></div>
            </div>
            <div>
                @foreach($leads as $item)
                <div class="grid grid-cols-12 p-3 px-3 mb-2 bg-white hover:bg-blue-50 rounded-lg shadow-sm font-normal cursor-pointer text-gray-600 text-sm">
                    <div class="col-span-2 flex gap-2 font-semibold">
                        <div class="flex items-center">
                            <input id="checkbox-{{$item->id}}" type="checkbox" value="{{$item->id}}" class="checkbox-child shrink-0 mt-0.5 border-gray-200 rounded-sm focus:ring-[#0077b5] disabled:opacity-50 disabled:pointer-events-none" style="accent-color: #0077b5;">
                        </div>
                        {{$item->name}}
                    </div>
                    <div class="col-span-3 email-cell-{{$item->id}}">
                        @if($item->email)
                            {{$item->email}}
                        @elseif(in_array(request()->query('src'), ['aud', 'sn'], true))
                            @if(!empty($item->email_fetch_status) && $item->email_fetch_status === 'pending')
                                <span class="text-blue-500 text-xs font-medium">Pending...</span>
                            @elseif(!empty($item->email_fetch_attempted_at) && empty($item->email_fetch_status))
                                @php
                                   
                                    $attemptedAt = \Carbon\Carbon::parse($item->email_fetch_attempted_at);
                                    $isRecent = $attemptedAt->diffInMinutes(now()) < 15;
                                @endphp
                                @if($isRecent)
                                    <span class="text-blue-500 text-xs font-medium">Pending...</span>
                                @else
                                    <span class="text-gray-500 text-xs">No email found</span>
                                @endif
                            @elseif(!empty($item->email_fetch_status) && $item->email_fetch_status === 'completed' && empty($item->email))
                                <span class="text-gray-500 text-xs">No email found</span>
                            @elseif(empty($item->email_fetch_status) && empty($item->email_fetch_attempted_at))
                                @if(isset($pendingEmailFetchCount) && $pendingEmailFetchCount >= 5)
                                    <span 
                                        class="text-gray-500 text-xs cursor-help relative group"
                                        title="You have {{ $pendingEmailFetchCount }} email scraping jobs in progress. Please come back in 45 minutes to allow other users to use the queue. This helps distribute the load across all users."
                                        data-tooltip="You have {{ $pendingEmailFetchCount }} email scraping jobs in progress. Please come back in 45 minutes to allow other users to use the queue. This helps distribute the load across all users.">
                                        Other emails processing...
                                        <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-normal w-64 z-50">
                                            You have {{ $pendingEmailFetchCount }} email scraping jobs in progress. Please come back in 45 minutes to allow other users to use the queue. This helps distribute the load across all users.
                                            <span class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1 border-4 border-transparent border-t-gray-900"></span>
                                        </span>
                                    </span>
                                @else
                                    <button 
                                        type="button" 
                                        class="get-email-btn inline-flex items-center justify-center rounded text-white px-3 py-1.5 text-xs transition-all" 
                                        style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" 
                                        onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" 
                                        onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';"
                                        data-lead-id="{{$item->id}}"
                                        data-list-src="{{ request()->query('src') }}"
                                        data-audience-list-id="{{$item->id}}"
                                        data-list-id="{{ request()->query('src') === 'sn' ? $listId : $item->list_hash }}">
                                        Get Email
                                    </button>
                                @endif
                            @else
                                <span class="text-gray-500 text-xs">No email found</span>
                            @endif
                        @else
                            <span class="text-gray-400">Not Found</span>
                        @endif
                    </div>
                    <div class="col-span-2">
                        {{$item->headline}}
                    </div>
                    <div class="col-span-2">
                        {{$item->location}}
                    </div>
                    <div class="col-span-2">{{date_format(date_create($item->created_at), "d M, Y")}}</div>
                    <div class="col-span-1"></div>
                    <div class="hs-dropdown relative inline-flex">
                        <button id="hs-dropdown-default-{{$item->id}}" type="button" class="hs-dropdown-toggle py-2 px-2 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg bg-white hover:bg-gray-50 focus:outline-hidden disabled:opacity-50 disabled:pointer-events-none" aria-haspopup="menu" aria-expanded="false" aria-label="Dropdown">
                            <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 4 15">
                                <path d="M3.5 1.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 6.041a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 5.959a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"/>
                            </svg>
                        </button>
                        <div class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-50 bg-white shadow-md rounded-lg mt-2 border border-gray-200 after:h-4 after:absolute after:-bottom-4 after:start-0 after:w-full before:h-4 before:absolute before:-top-4 before:start-0 before:w-full" role="menu" aria-orientation="vertical" aria-labelledby="hs-dropdown-default-{{$item->id}}">
                            <div class="p-1 space-y-0.5">
                                <a href="https://www.linkedin.com/in/{{$item->profileid}}" target="_blank" class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">
                                    <svg class="w-5 h-5 text-[#0077b5]" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M12.51 8.796v1.697a3.738 3.738 0 0 1 3.288-1.684c3.455 0 4.202 2.16 4.202 4.97V19.5h-3.2v-5.072c0-1.21-.244-2.766-2.128-2.766-1.827 0-2.139 1.317-2.139 2.676V19.5h-3.19V8.796h3.168ZM7.2 6.106a1.61 1.61 0 0 1-.988 1.483 1.595 1.595 0 0 1-1.743-.348A1.607 1.607 0 0 1 5.6 4.5a1.601 1.601 0 0 1 1.6 1.606Z" clip-rule="evenodd"/>
                                        <path d="M7.2 8.809H4V19.5h3.2V8.809Z"/>
                                    </svg>
                                    View profile
                                </a>
                                <form action="{{route('leads.remove_lead', ['leadId' => $item->id])}}" method="POST">
                                    @csrf
                                    @method('DELETE') 
                                    <input type="hidden" name="src" value="{{request()->query('src')}}"> 
                                    <input type="hidden" name="list_id" value="{{$item->list_hash}}">
                                    <button type="submit" class="flex w-full items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                                            <path fill-rule="evenodd" d="M16.5 4.478v.227a48.816 48.816 0 013.878.512.75.75 0 11-.256 1.478l-.209-.035-1.005 13.07a3 3 0 01-2.991 2.77H8.084a3 3 0 01-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 01-.256-1.478A48.567 48.567 0 017.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 013.369 0c1.603.051 2.815 1.387 2.815 2.951zm-6.136-1.452a51.196 51.196 0 013.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 00-6 0v-.113c0-.794.609-1.428 1.364-1.452zm-.355 5.945a.75.75 0 10-1.5.058l.347 9a.75.75 0 101.499-.058l-.346-9zm5.48.058a.75.75 0 10-1.498-.058l-.347 9a.75.75 0 001.5.058l.345-9z" clip-rule="evenodd" />
                                        </svg>
                                        Remove lead
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @if(count($leads)>0)
            {{ $leads->links() }}
            @endif
        </div>
    </div>
</div>

<script>
let listVal = []
let listHash = $('#list-hash').val()
let listSrc = $('#list-src').val()
let listName = $('#list-name').val()

$('.checkbox-main').click(function(ev){
    let checked = ev.target.checked
    $('.checkbox-child').prop('checked', checked)

    listVal = []
    if(checked){
        $('.checkbox-child').each(function() {
            listVal.push($(this).val())
        })
    }
    
    toogleActions()
})

$('.checkbox-child').click(function(ev){
    let checked = ev.target.checked;

    if(checked == false)
        listVal = listVal.filter((item) => item != $(this).val())
    else
        listVal.push($(this).val())

    if($('.checkbox-child').length == listVal.length)
        $('.checkbox-main').prop('checked', true)
    else
        $('.checkbox-main').prop('checked', false)

    toogleActions()
})

const toogleActions = () => {
    if(listVal.length)
        $('.check-actions').show()
    else
        $('.check-actions').hide()

    $('#delete-form').attr('action', `/leads/remove/bulk/${listHash}?src=${listSrc}&ids=${listVal.toString()}`)
}

$('.export').click(function() {
    const date = new Date();
    const d = date.getDate();
    const m = date.getMonth();
    const y = date.getFullYear();
    const mtn = date.getMinutes();
    const fileName =  `${listName}_${d}${m}${y}${mtn}.csv`;

    $.ajax({
        method: 'get',
        url: `/leads/export/bulk?src=${listSrc}&ids=${listVal.toString()}&format=csv`,
        success: function(res) {
            const csvRows = [];
            const headers = Object.keys(res.data[0]);
            csvRows.push(headers.join(','));

            for (const row of res.data) {
                const values = headers.map(header => {
                    const val = row[header]
                    return `"${val}"`;
                });
        
                // To add, sepearater between each value
                csvRows.push(values.join(','));
            }
            const download = csvRows.join('\n');
        
            var blob = new Blob([download]);
            var link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.download = fileName;
            link.click();
        }
    })
})

// Email enrichment (audience + Sales Navigator)
$(document).ready(function() {
    const pageListSrc = $('#list-src').val();
    const pageListId = $('#list-hash').val();

    function loadDailyLimit() {
        if (!$('#daily-limit-info').length) {
            return;
        }

        $.ajax({
            url: '/leads/daily-limit',
            method: 'GET',
            timeout: 10000,
            success: function(response) {
                if (response && typeof response.used !== 'undefined') {
                    $('#daily-limit-used').text(response.used || 0);
                    const percentage = ((response.used || 0) / (response.daily_limit || 100)) * 100;
                    $('#daily-limit-progress').css('width', percentage + '%');

                    const remaining = response.remaining || 0;

                    if (remaining <= 0) {
                        $('#daily-limit-status').html('<span class="text-red-600 font-semibold">Limit Reached</span>');
                        $('#daily-limit-progress').removeClass('bg-blue-600').addClass('bg-red-600');
                    } else if (remaining < 20) {
                        $('#daily-limit-status').html(`<span class="text-yellow-600 font-semibold">${remaining} remaining</span>`);
                        $('#daily-limit-progress').removeClass('bg-blue-600').addClass('bg-yellow-600');
                    } else {
                        $('#daily-limit-status').html(`<span class="text-green-600 font-semibold">${remaining} remaining</span>`);
                        $('#daily-limit-progress').removeClass('bg-yellow-600 bg-red-600').addClass('bg-blue-600');
                    }
                } else {
                    $('#daily-limit-used').text('0');
                    $('#daily-limit-status').html('<span class="text-gray-500">Unable to load</span>');
                }
            },
            error: function() {
                $('#daily-limit-used').text('0');
                $('#daily-limit-status').html('<span class="text-gray-500">Error loading</span>');
            }
        });
    }

    if (pageListSrc === 'aud') {
        loadDailyLimit();
    }

    let pendingEmailFetchCount = {{ $pendingEmailFetchCount ?? 0 }};
    let pendingCountPollInterval = null;
    const pendingBatchLimit = {{ (int) config('services.email_scraping.batch_size', 25) }};

    // Function to update pending count from backend
    function updatePendingCount() {
        $.ajax({
            url: '/leads/pending-count',
            method: 'GET',
            success: function(response) {
                if (response.status === 'success') {
                    const newCount = response.pending_count || 0;
                    if (newCount !== pendingEmailFetchCount) {
                        pendingEmailFetchCount = newCount;
                        updateEmailButtonStates();
                    }
                }
            },
            error: function() {
                // Silently fail, will retry on next poll
            }
        });
    }

    // Function to update all email button states based on pending count
    function updateEmailButtonStates() {
        const pendingLimitReached = pendingEmailFetchCount >= pendingBatchLimit;
        const tooltipText = `You have ${pendingEmailFetchCount} email scraping jobs in progress. Please come back in 45 minutes to allow other users to use the queue. This helps distribute the load across all users.`;
        
        // Update all "Get Email" buttons
        $('.get-email-btn').each(function() {
            const btn = $(this);
            const audienceListId = btn.data('audience-list-id');
            const emailCell = $(`.email-cell-${audienceListId}`);
            
            // Only update buttons that are in "never attempted" state
            if (emailCell.length && emailCell.find('.get-email-btn').length > 0) {
                if (pendingLimitReached) {
                    // Replace button with "Other emails processing..." message
                    btn.replaceWith(`
                        <span 
                            class="text-gray-500 text-xs cursor-help relative group inline-block"
                            title="${tooltipText}"
                            data-tooltip="${tooltipText}">
                            Other emails processing...
                            <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-normal w-64 z-50">
                                ${tooltipText}
                                <span class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1 border-4 border-transparent border-t-gray-900"></span>
                            </span>
                        </span>
                    `);
                }
            }
        });
        
        // Also update cells that show "Email not fetched" to show "Other emails processing..." if limit reached
        if (pendingLimitReached) {
            $('.email-cell-*').each(function() {
                const cell = $(this);
                if (cell.find('span.text-gray-400:contains("Email not fetched")').length > 0 && 
                    cell.find('.get-email-btn').length === 0) {
                    cell.html(`
                        <span 
                            class="text-gray-500 text-xs cursor-help relative group"
                            title="${tooltipText}"
                            data-tooltip="${tooltipText}">
                            Other emails processing...
                            <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-normal w-64 z-50">
                                ${tooltipText}
                                <span class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1 border-4 border-transparent border-t-gray-900"></span>
                            </span>
                        </span>
                    `);
                }
            });
        }
    }

    // Start polling for pending count updates (every 5 seconds)
    function startPendingCountPolling() {
        if (pendingCountPollInterval) {
            clearInterval(pendingCountPollInterval);
        }
        pendingCountPollInterval = setInterval(updatePendingCount, 5000);
    }

    // Stop polling
    function stopPendingCountPolling() {
        if (pendingCountPollInterval) {
            clearInterval(pendingCountPollInterval);
            pendingCountPollInterval = null;
        }
    }

    updateEmailButtonStates();
    if (pageListSrc === 'aud') {
        startPendingCountPolling();
    }

    $(document).on('click', '.get-email-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const btn = $(this);
        const audienceListId = btn.data('audience-list-id');
        const leadId = btn.data('lead-id');
        const listId = btn.data('list-id');
        const listSrc = btn.data('list-src') || 'aud';
        
        // Check if limit is reached before making request
        // Audience lists queue jobs; SN runs synchronously and is not gated by pending count
        if (listSrc !== 'sn' && pendingEmailFetchCount >= pendingBatchLimit) {
            showNotification('error', `You have ${pendingEmailFetchCount} enrichment jobs in progress. Please wait for the current batch to finish.`);
            return;
        }
        
        // Disable button and show loading state
        btn.prop('disabled', true);
        const originalHtml = btn.html();
        btn.html('<svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span class="ml-2">Processing...</span>');
        
        const postData = { _token: '{{ csrf_token() }}' };
        if (listSrc === 'sn') {
            postData.lead_id = leadId;
        } else {
            postData.audience_list_id = audienceListId;
        }

        $.ajax({
            url: `/leads/${listId}/fetch-email?src=${listSrc}`,
            method: 'POST',
            data: postData,
            timeout: listSrc === 'sn' ? 120000 : 30000,
            success: function(response) {
                if (response.status === 'success') {
                    if (listSrc === 'sn') {
                        if (response.email) {
                            $(`.email-cell-${audienceListId}`).text(response.email);
                        } else {
                            $(`.email-cell-${audienceListId}`).html('<span class="text-gray-500 text-xs">No email found</span>');
                        }
                        btn.remove();
                        showNotification('success', response.message || (response.email ? 'Email found.' : 'No email found for this profile.'));
                        loadDailyLimit();
                        return;
                    }

                    // Increment local pending count for queued jobs
                    pendingEmailFetchCount++;
                    updateEmailButtonStates();
                    
                    showNotification('success', response.message || 'Email fetch job queued. Please wait while we fetch the email.');
                    // Update the email cell to show "Pending..." state
                    $(`.email-cell-${audienceListId}`).html('<span class="text-blue-500 text-xs font-medium">Pending...</span>');
                    // Hide the button since email fetch is queued
                    btn.remove();
                    // Refresh daily limit display after successful dispatch
                    setTimeout(function() {
                        loadDailyLimit();
                    }, 1000); // Wait 1 second for the job to process and update the count
                } else {
                    showNotification('error', response.message || 'Failed to fetch email');
                    btn.prop('disabled', false);
                    btn.html(originalHtml);
                }
            },
            error: function(xhr) {
                let errorMessage = 'Failed to fetch email. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                // Handle concurrent limit error
                if (xhr.status === 429 && xhr.responseJSON && xhr.responseJSON.concurrent_limit_reached) {
                    pendingEmailFetchCount = xhr.responseJSON.pending_count || pendingEmailFetchCount;
                    updateEmailButtonStates();
                    showNotification('error', errorMessage);
                    btn.prop('disabled', false);
                    btn.html(originalHtml);
                    return;
                }
                
                // If already pending, update UI to show pending state
                if (xhr.status === 409 && xhr.responseJSON && xhr.responseJSON.already_pending) {
                    $(`.email-cell-${audienceListId}`).html('<span class="text-blue-500 text-xs font-medium">Pending...</span>');
                    btn.remove();
                    showNotification('info', 'Email fetch is already in progress. Please wait.');
                    // Update pending count
                    updatePendingCount();
                } else {
                    showNotification('error', errorMessage);
                    btn.prop('disabled', false);
                    btn.html(originalHtml);
                }
            }
        });
    });

    // Show notification
    function showNotification(type, message) {
        const colors = {
            success: '#10b981',
            error: '#ef4444',
            info: '#0077b5',
        };
        const bgColor = colors[type] || colors.error;
        const icon = type === 'success' ? '✓' : (type === 'info' ? 'ℹ' : '✕');
        const notification = $(`
            <div style="position: fixed; top: 20px; right: 20px; z-index: 99999; background: white; border-left: 4px solid ${bgColor}; padding: 16px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); min-width: 300px; max-width: 500px;">
                <div class="flex items-center gap-3">
                    <div style="color: ${bgColor}; font-size: 20px;">${icon}</div>
                    <div class="text-gray-900 text-sm">${message}</div>
                </div>
            </div>
        `);
        
        $('body').append(notification);
        
        setTimeout(function() {
            notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
});
</script>
@endsection