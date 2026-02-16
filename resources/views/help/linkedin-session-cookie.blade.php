@extends('layout.guest')

@section('content')
    <div class="mt-6 bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
        <h1 class="text-xl font-semibold text-gray-900">How to get your LinkedIn li_at cookie</h1>
        <p class="text-sm text-gray-600 mt-2">
            You must be logged in to LinkedIn in your browser to see this cookie.
        </p>

        <ol class="mt-4 space-y-3 text-sm text-gray-700 list-decimal list-inside">
            <li>Open <span class="font-medium">linkedin.com</span> and sign in.</li>
            <li>Right-click the page and choose <span class="font-medium">Inspect</span>.</li>
            <li>In DevTools, open the <span class="font-medium">Application</span> tab.</li>
            <li>In the left sidebar, expand <span class="font-medium">Cookies</span>.</li>
            <li>Click <span class="font-medium">https://www.linkedin.com</span>.</li>
            <li>Find the cookie named <span class="font-medium">li_at</span>.</li>
            <li>Copy the value and paste it where required.</li>
        </ol>
    </div>
@endsection
