@extends('layout.guest')
@seo([
        'title' => 'Bundle Access Signup - ' . config('app.name'),
        'description' => 'LinkedIn Lead Generation And Sales Automation Tool',
        'image' => asset('images/site-image.png'),
        'site_name' => config('app.name'),
        'favicon' => asset('images/logo-1.png'),
    ])
@section('content')
<div class="mt-7 bg-white border border-gray-200 rounded-xl shadow-2xs">
    <div class="p-4 sm:p-7">
        <div class="text-center">
        <h1 class="block text-2xl font-bold text-gray-900">Bundle Access Signup</h1>
        <p class="mt-2 text-sm text-gray-600">
            Already have an account?
            <a class="text-[#0077b5] decoration-2 hover:text-[#005885] hover:underline focus:outline-hidden focus:underline font-medium" href="{{route('auth.login')}}">
            Sign in here
            </a>
        </p>
        </div>

        <div class="mt-5">

        <!-- Form -->
        <form method="post" action="{{route('register.bundle.auth')}}">
            @csrf
            <div class="grid gap-y-4">
                <div>
                    <label for="name" class="block text-sm mb-2 text-gray-700">Full Name</label>
                    <div class="relative">
                    <input type="text" id="name" name="name" value="{{ old('name') }}" class="py-2.5 sm:py-3 px-4 block w-full border-gray-200 rounded-lg sm:text-sm focus:border-[#0077b5] focus:ring-[#0077b5] disabled:opacity-50 disabled:pointer-events-none" required aria-describedby="name-error">
                    <div class="hidden absolute inset-y-0 end-0 pointer-events-none pe-3">
                        <svg class="size-5 text-red-500" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
                        </svg>
                    </div>
                </div>
            </div>
            <!-- Form Group -->
            <div>
                <label for="email" class="block text-sm mb-2 text-gray-700">Email address</label>
                <div class="relative">
                <input type="email" id="email" name="email" value="{{ old('email') }}" class="py-2.5 sm:py-3 px-4 block w-full border-gray-200 rounded-lg sm:text-sm focus:border-[#0077b5] focus:ring-[#0077b5] disabled:opacity-50 disabled:pointer-events-none" required aria-describedby="email-error">
                <div class="hidden absolute inset-y-0 end-0 pointer-events-none pe-3">
                    <svg class="size-5 text-red-500" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
                    </svg>
                </div>
                </div>
                <p class="hidden text-xs text-red-600 mt-2" id="email-error">Please include a valid email address so we can get back to you</p>
            </div>
            <!-- End Form Group -->

            <!-- Form Group -->
            <div>
                <label for="password" class="block text-sm mb-2 text-gray-700">Password</label>
                <div class="relative">
                <input type="password" id="password" name="password" class="py-2.5 sm:py-3 px-4 block w-full border-gray-200 rounded-lg sm:text-sm focus:border-[#0077b5] focus:ring-[#0077b5] disabled:opacity-50 disabled:pointer-events-none" required aria-describedby="password-error">
                <div class="hidden absolute inset-y-0 end-0 pointer-events-none pe-3">
                    <svg class="size-5 text-red-500" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
                    </svg>
                </div>
                </div>
            </div>
            <!-- End Form Group -->

                <button type="submit" class="w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent text-white transition-all disabled:opacity-50 disabled:pointer-events-none" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">Sign up</button>
            </div>
        </form>
        <!-- End Form -->
        </div>
    </div>
</div>
@endsection