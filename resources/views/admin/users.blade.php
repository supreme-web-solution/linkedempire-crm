@extends('layout.auth')

@section('content')

<div class="flex justify-between">
    <h2 class="text-2xl font-semibold text-gray-900">
        Manage users
    </h2>
    <div class="flex gap-2">
        <form action="" method="get">
            <label class="block text-sm">
                <div class="relative text-gray-500 focus-within:text-[#0077b5]">
                    <input class="block w-full pr-20 mt-1 text-sm text-black 
                    focus:border-[#0077b5] focus:outline-none rounded-md
                    focus:ring-2 focus:ring-[#0077b5] 
                    form-input border-gray-300"
                    placeholder="Search email"
                    name="email" value="{{request()->query('email')}}"/>
                    <button 
                    type="submit"
                    class="absolute inset-y-0 right-0 px-4 text-sm 
                    font-medium leading-5 text-white transition-all 
                    duration-150 border border-transparent 
                    rounded-r-md"
                    style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);"
                    onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)';"
                    onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)';">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                            <path fill-rule="evenodd" d="M10.5 3.75a6.75 6.75 0 100 13.5 6.75 6.75 0 000-13.5zM2.25 10.5a8.25 8.25 0 1114.59 5.28l4.69 4.69a.75.75 0 11-1.06 1.06l-4.69-4.69A8.25 8.25 0 012.25 10.5z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </label>
        </form>
        <button 
        type="button"
        class="block px-4 py-2 text-sm font-medium leading-2 
        text-white transition-all duration-150 
        border border-transparent rounded-lg flex open-new-user-modal"
        style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);"
        onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';"
        onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';"
        aria-haspopup="dialog" aria-expanded="false" aria-controls="hs-scale-animation-modal-newuser" data-hs-overlay="#hs-scale-animation-modal-newuser">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                <path fill-rule="evenodd" d="M12 5.25a.75.75 0 01.75.75v5.25H18a.75.75 0 010 1.5h-5.25V18a.75.75 0 01-1.5 0v-5.25H6a.75.75 0 010-1.5h5.25V6a.75.75 0 01.75-.75z" clip-rule="evenodd" />
            </svg>
            <span class="mt-2">New user</span>
        </button>
    </div>
</div>

<div class="mt-8">
    <div class="w-full overflow-hidden rounded-lg shadow-xs">
        <div class="w-full overflow-x-auto">
            <table class="w-full whitespace-no-wrap">
                <thead>
                    <tr class="text-xs font-semibold tracking-wide 
                    text-left text-gray-500 uppercase border-b 
                    bg-gray-50">
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">LinkedIn ID</th>
                        <th class="px-4 py-3">Date Created</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($users as $user)
                    <tr class="text-gray-700 user-list-{{$user->id}} text-sm">
                        <td class="px-4 py-3">{{ $user->name }}</td>
                        <td class="px-4 py-3">{{ $user->email }}</td>
                        <td class="px-4 py-3">{{ $user->linkedin_id }}</td>
                        <td class="px-4 py-3">{{ date_format(date_create($user->created_at), "d M, Y") }}</td>
                        <td class="px-4 py-3">
                            <div class="flex gap-3">
                                <!-- update -->
                                <button
                                type="button"
                                class="open-update-user-modal text-[#0077b5] hover:text-[#005885] transition-colors"
                                data-userid="{{$user->id}}" 
                                data-name="{{$user->name}}" 
                                data-email="{{$user->email}}"
                                data-linkedin="{{$user->linkedin_id}}"
                                aria-haspopup="dialog" aria-expanded="false" aria-controls="hs-scale-animation-modal-updateuser" data-hs-overlay="#hs-scale-animation-modal-updateuser">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                                    <path d="M21.731 2.269a2.625 2.625 0 00-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 000-3.712zM19.513 8.199l-3.712-3.712-8.4 8.4a5.25 5.25 0 00-1.32 2.214l-.8 2.685a.75.75 0 00.933.933l2.685-.8a5.25 5.25 0 002.214-1.32l8.4-8.4z" />
                                    <path d="M5.25 5.25a3 3 0 00-3 3v10.5a3 3 0 003 3h10.5a3 3 0 003-3V13.5a.75.75 0 00-1.5 0v5.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5V8.25a1.5 1.5 0 011.5-1.5h5.25a.75.75 0 000-1.5H5.25z" />
                                    </svg>
                                </button>
                                <!-- Assign permission -->
                                <button 
                                type="button"
                                class="assign-permission text-[#0077b5] hover:text-[#005885] transition-colors"
                                data-userid="{{$user->id}}" 
                                data-name="{{$user->name}}"
                                aria-haspopup="dialog" aria-expanded="false" aria-controls="hs-scale-animation-modal-permission" data-hs-overlay="#hs-scale-animation-modal-permission">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                                    <path fill-rule="evenodd" d="M12.516 2.17a.75.75 0 00-1.032 0 11.209 11.209 0 01-7.877 3.08.75.75 0 00-.722.515A12.74 12.74 0 002.25 9.75c0 5.942 4.064 10.933 9.563 12.348a.749.749 0 00.374 0c5.499-1.415 9.563-6.406 9.563-12.348 0-1.39-.223-2.73-.635-3.985a.75.75 0 00-.722-.516l-.143.001c-2.996 0-5.717-1.17-7.734-3.08zm3.094 8.016a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <!-- Impersonate -->
                                <form action="{{ route('user.impersonate', ['id' => $user->id]) }}" method="post" onsubmit="return confirm('You will be logged out of your current account and logged in as this user. Continue?');">
                                    @csrf
                                    <button 
                                    type="submit"
                                    class="text-purple-600 hover:text-purple-800 transition-colors" 
                                    title="Login as this user">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                                            <path d="M11.25 4.5a.75.75 0 000 1.5h6.69l-8.72 8.72a.75.75 0 101.06 1.06l8.72-8.72v6.69a.75.75 0 001.5 0v-9a.75.75 0 00-.75-.75h-9z" />
                                            <path d="M5.25 5.25A2.25 2.25 0 003 7.5v11.25A2.25 2.25 0 005.25 21h11.25a2.25 2.25 0 002.25-2.25v-2.5a.75.75 0 00-1.5 0v2.5a.75.75 0 01-.75.75H5.25a.75.75 0 01-.75-.75V7.5a.75.75 0 01.75-.75h2.5a.75.75 0 000-1.5h-2.5z" />
                                        </svg>
                                    </button>
                                </form>
                                <!-- Delete -->
                                <form action="{{route('user.delete', ['id' => $user->id])}}" method="post">
                                    @csrf
                                    @method('DELETE')
                                    <button 
                                    type="submit"
                                    class="open-delete-user-modal text-red-600 hover:text-red-700 transition-colors" 
                                    data-userid="{{$user->id}}" 
                                    data-name="{{$user->name}}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                                        <path fill-rule="evenodd" d="M16.5 4.478v.227a48.816 48.816 0 013.878.512.75.75 0 11-.256 1.478l-.209-.035-1.005 13.07a3 3 0 01-2.991 2.77H8.084a3 3 0 01-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 01-.256-1.478A48.567 48.567 0 017.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 013.369 0c1.603.051 2.815 1.387 2.815 2.951zm-6.136-1.452a51.196 51.196 0 013.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 00-6 0v-.113c0-.794.609-1.428 1.364-1.452zm-.355 5.945a.75.75 0 10-1.5.058l.347 9a.75.75 0 101.499-.058l-.346-9zm5.48.058a.75.75 0 10-1.498-.058l-.347 9a.75.75 0 001.5.058l.345-9z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($users->count()>0)
        {{ $users->links() }}
        @endif
    </div>
</div>

<!-- New user -->
<div id="hs-scale-animation-modal-newuser" class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none" role="dialog" tabindex="-1" aria-labelledby="hs-scale-animation-modal-label-newuser">
    <div class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
        <div class="w-full flex flex-col bg-white border border-gray-200 shadow-2xs rounded-xl pointer-events-auto">
        <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200">
            <h3 id="hs-scale-animation-modal-label-newuser" class="font-bold text-gray-800">
            New user
            </h3>
            <button type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-full border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 focus:outline-hidden focus:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none" aria-label="Close" data-hs-overlay="#hs-scale-animation-modal-newuser">
            <span class="sr-only">Close</span>
            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 6 6 18"></path>
                <path d="m6 6 12 12"></path>
            </svg>
            </button>
        </div>
        <form action="{{route('user.store')}}" method="post">
            @csrf
            <div class="p-4 overflow-y-auto">
                <div class="w-full">
                    <label class="block text-sm w-full">
                        <span class="text-gray-700">Name</span>
                        <input
                        type="text"
                        class="block w-full mt-1 text-sm focus:border-[#0077b5] focus:outline-none 
                        focus:ring-2 focus:ring-[#0077b5] form-input rounded-md border-gray-300"
                        placeholder="John Doe"
                        id="name" name="name" value="{{ old('name') }}"
                        required
                        />
                    </label>
                </div>
                <div class="mt-4 w-full">
                    <label class="block text-sm w-full">
                        <span class="text-gray-700">Email</span>
                        <input
                        type="email"
                        class="block w-full mt-1 text-sm focus:border-[#0077b5] focus:outline-none 
                        focus:ring-2 focus:ring-[#0077b5] form-input rounded-md border-gray-300"
                        placeholder="john@example.com"
                        id="email" name="email" value="{{ old('email') }}"
                        required
                        />
                    </label>
                </div>
                <div class="mt-4 w-full">
                    <label class="block text-sm w-full">
                        <span class="text-gray-700">Password</span>
                        <input
                        type="password"
                        class="block w-full mt-1 text-sm focus:border-[#0077b5] focus:outline-none 
                        focus:ring-2 focus:ring-[#0077b5] form-input rounded-md border-gray-300"
                        placeholder="************"
                        id="password" name="password"
                        required />
                    </label>
                </div>
            </div>
            <div class="flex justify-end items-center gap-x-2 py-3 px-4 border-t border-gray-200">
                <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-2xs hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none" data-hs-overlay="#hs-scale-animation-modal-newuser">
                Close
                </button>
                <button type="submit" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent text-white transition-all disabled:opacity-50 disabled:pointer-events-none" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">
                Save
                </button>
            </div>
        </form>
        </div>
    </div>
</div>

<!-- Update user -->
<div id="hs-scale-animation-modal-updateuser" class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none" role="dialog" tabindex="-1" aria-labelledby="hs-scale-animation-modal-label-updateuser">
    <div class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
        <div class="w-full flex flex-col bg-white border border-gray-200 shadow-2xs rounded-xl pointer-events-auto">
        <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200">
            <h3 id="hs-scale-animation-modal-label-updateuser" class="font-bold text-gray-800">
            Update user
            </h3>
            <button type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-full border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 focus:outline-hidden focus:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none" aria-label="Close" data-hs-overlay="#hs-scale-animation-modal-updateuser">
            <span class="sr-only">Close</span>
            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 6 6 18"></path>
                <path d="m6 6 12 12"></path>
            </svg>
            </button>
        </div>
        <form action="" method="post" id="update-form">
            @csrf
            @method('PUT')
            <div class="p-4 overflow-y-auto">
                <div class="w-full">
                    <label class="block text-sm w-full">
                        <span class="text-gray-700">Name</span>
                        <input
                        type="text"
                        class="block w-full mt-1 text-sm focus:border-[#0077b5] focus:outline-none 
                        focus:ring-2 focus:ring-[#0077b5] form-input rounded-md border-gray-300"
                        placeholder="John Doe"
                        id="update-name" name="name" value="{{ old('name') }}"
                        required
                        />
                    </label>
                </div>
                <div class="mt-4 w-full">
                    <label class="block text-sm w-full">
                        <span class="text-gray-700">Email</span>
                        <input
                        type="email"
                        class="block w-full mt-1 text-sm focus:border-[#0077b5] focus:outline-none 
                        focus:ring-2 focus:ring-[#0077b5] form-input rounded-md border-gray-300"
                        placeholder="john@example.com"
                        id="update-email" name="email" value="{{ old('email') }}"
                        required
                        />
                    </label>
                </div>
                <div class="mt-4 w-full">
                    <label class="block text-sm w-full">
                        <span class="text-gray-700">LinkedIn ID</span>
                        <input
                        type="text"
                        class="block w-full mt-1 text-sm focus:border-[#0077b5] focus:outline-none 
                        focus:ring-2 focus:ring-[#0077b5] form-input rounded-md border-gray-300"
                        id="update-linkedin" name="linkedin_id" placeholder="Linkedin ID"
                        />
                    </label>
                </div>
                <div class="mt-4 w-full">
                    <label class="block text-sm w-full">
                        <span class="text-gray-700">Password</span>
                        <input
                        type="password"
                        class="block w-full mt-1 text-sm focus:border-[#0077b5] focus:outline-none 
                        focus:ring-2 focus:ring-[#0077b5] form-input rounded-md border-gray-300"
                        placeholder="************"
                        id="update-pass" name="password" />
                    </label>
                </div>
            </div>
            <div class="flex justify-end items-center gap-x-2 py-3 px-4 border-t border-gray-200">
                <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-2xs hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none" data-hs-overlay="#hs-scale-animation-modal-updateuser">
                Close
                </button>
                <button type="submit" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent text-white transition-all disabled:opacity-50 disabled:pointer-events-none" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">
                Save
                </button>
            </div>
        </form>
        </div>
    </div>
</div>

<!-- Assign permission -->
<div id="hs-scale-animation-modal-permission" class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none" role="dialog" tabindex="-1" aria-labelledby="hs-scale-animation-modal-label-permission">
    <div class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
        <div class="w-full flex flex-col bg-white border border-gray-200 shadow-2xs rounded-xl pointer-events-auto">
        <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200">
            <h3 id="hs-scale-animation-modal-label-permission" class="font-bold text-gray-800">
            Assign permission
            </h3>
            <button type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-full border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 focus:outline-hidden focus:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none" aria-label="Close" data-hs-overlay="#hs-scale-animation-modal-permission">
            <span class="sr-only">Close</span>
            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 6 6 18"></path>
                <path d="m6 6 12 12"></path>
            </svg>
            </button>
        </div>
        <form action="{{route('user.assign-permissions')}}" method="post" id="permission-form">
            @csrf
            @method('PUT')
            <div class="p-4 overflow-y-auto">
                <div class="perm-body grid grid-cols-10 gap-4"></div>
                <span class="notice mt-8"></span>
                <input type="hidden" id="perm-user-id" name="userId">
            </div>
            <div class="flex justify-end items-center gap-x-2 py-3 px-4 border-t border-gray-200">
                <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-2xs hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none" data-hs-overlay="#hs-scale-animation-modal-permission">
                Close
                </button>
                <button type="submit" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent text-white transition-all disabled:opacity-50 disabled:pointer-events-none confirm-assign-perm" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">
                Assign
                </button>
            </div>
        </form>
        </div>
    </div>
</div>

<script>
$('.open-update-user-modal').click(function() {
    let username = $(this).data('name'),
        email = $(this).data('email'),
        userid = $(this).data('userid'),
        linkedinId = $(this).data('linkedin');

    $('#update-form').attr('action', `/admin/user/update/${userid}`)
    $('#update-name').val(username)
    $('#update-email').val(email)
    $('#update-pass').val('')
    $('#update-linkedin').val(linkedinId)
})

$('.assign-permission').click(function(){
    let username = $(this).data('name')
    let userid = $(this).data('userid')
    let display = ''

    $.ajax({
        beforeSend: function(request) {
            request.setRequestHeader("X-CSRF-TOKEN", "{{ csrf_token() }}")
        },
        method: 'get',
        url: `/admin/user/permissions?userid=${userid}`,
        success: function(data){
            if(data.message == 'success'){
                $('.perm-title').text(`Assign permissions - ${username}`)
                $('#perm-user-id').val(userid)
                $('.notice').empty()
                $('.perm-body').empty()
                $.each(data.perm, function(i, item){
                    display = `
                        <div class="col-span-2 mb-3">
                            <input type="checkbox" name="permissions[]" class="perm-name rounded" id="perm-${item.id}" value="${item.name}" 
                                ${ item.model_id == userid ? 'checked' : '' } >
                            <label for="perm-${item.id}">${item.name}</label>
                        </div>
                    `;
                    $('.perm-body').append(display)
                })
                $('.permission-modal').show()
            }
        }
    })
})
</script>
@endsection