@extends('layout.guest-signup')
@seo([
        'title' => 'Bundle Signup - ' . config('app.name'),
        'description' => 'LinkedIn Lead Generation And Sales Automation Tool',
        'image' => asset('images/site-image.png'),
        'site_name' => config('app.name'),
        'favicon' => asset('images/logo-1.png'),
    ])
@section('header-message')
<div class="w-full max-w-4xl mx-auto mb-4">
    <div class="bg-white border border-gray-200 rounded-lg p-4 sm:p-5 shadow-sm">
        <div class="text-center space-y-2">
            <p class="text-lg font-semibold text-gray-900">Hey there,</p>
            <p class="text-sm text-gray-700">Thank you for your patronage. You have made a great decision, as this app is designed to deliver results.</p>
            
            <div class="mt-3 mx-auto max-w-xl">
                <div class="bg-gradient-to-r from-yellow-50 via-amber-50 to-yellow-50 border-2 border-yellow-400 rounded-lg p-3 shadow-sm">
                    <div class="flex items-center justify-center gap-2 mb-1">
                        <svg class="w-4 h-4 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-sm font-bold text-yellow-900">IMPORTANT NOTICE</p>
                    </div>
                    <p class="text-xs text-gray-800">Please create your Full Bundle Option account using your preferred details below.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="w-full max-w-5xl mx-auto">
<div class="w-full grid grid-cols-1 lg:grid-cols-2 gap-0 min-h-[420px] rounded-xl overflow-hidden border border-gray-200 shadow-lg bg-white">
    <!-- Left: Logo (full-width column) -->
    <div class="p-6 sm:p-8 lg:p-10 bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 border-b lg:border-b-0 lg:border-r border-blue-200/60 order-2 lg:order-1 flex flex-col justify-center items-center relative overflow-hidden">
        <!-- Decorative background elements -->
        <div class="absolute top-0 right-0 w-32 h-32 bg-blue-100/30 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-24 h-24 bg-indigo-100/30 rounded-full blur-2xl"></div>
        
        <div class="relative z-10 flex flex-col items-center">
            <div class="flex justify-center mb-4">
                <x-app-logo/>
            </div>
            <p class="text-sm text-gray-600 font-medium text-center">A platform for building something big on LinkedIn</p>
        </div>
    </div>

    <!-- Right: Form (full-width column) -->
    <div class="p-6 sm:p-8 lg:p-10 order-1 lg:order-2 flex flex-col justify-center">
        <div class="text-center lg:text-left">
            <h1 class="block text-2xl font-bold text-gray-900">Bundle Signup</h1>
            <p class="mt-2 text-sm text-gray-600">
                Already have an account?
                <a class="text-[#0077b5] decoration-2 hover:text-[#005885] hover:underline focus:outline-hidden focus:underline font-medium" href="{{route('auth.login')}}">
                Sign in here
                </a>
            </p>
        </div>
        <div class="mt-5">
        <!-- Form -->
        <form method="post" action="{{route('register.bundle.auth.new')}}">
            @csrf
            <div class="grid gap-y-4">
                <div>
                    <label for="name" class="block text-sm mb-2 text-gray-700">Full Name</label>
                    <div class="relative">
                    <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="Enter your full name" class="py-2.5 sm:py-3 px-4 block w-full border-gray-200 rounded-lg sm:text-sm focus:border-[#0077b5] focus:ring-[#0077b5] disabled:opacity-50 disabled:pointer-events-none" required aria-describedby="name-error">
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
                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="your@email.com" class="py-2.5 sm:py-3 px-4 block w-full border-gray-200 rounded-lg sm:text-sm focus:border-[#0077b5] focus:ring-[#0077b5] disabled:opacity-50 disabled:pointer-events-none" required aria-describedby="email-error">
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
                <label for="bundle-password" class="block text-sm mb-2 text-gray-700">Password</label>
                <div class="relative">
                <input type="password" id="bundle-password" name="password" placeholder="Enter your password" class="py-2.5 sm:py-3 px-4 pe-10 block w-full border-gray-200 rounded-lg sm:text-sm focus:border-[#0077b5] focus:ring-[#0077b5] disabled:opacity-50 disabled:pointer-events-none" required aria-describedby="password-error">
                <button type="button" onclick="toggleBundlePassword()"
                    class="absolute inset-y-0 end-0 flex items-center pe-3 cursor-pointer hover:text-[#0077b5] transition-colors"
                    aria-label="Toggle password visibility">
                    <svg id="bundle-password-eye" class="size-5 text-gray-400" width="20" height="20"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                        </path>
                    </svg>
                    <svg id="bundle-password-eye-slash" class="size-5 text-gray-400 hidden" width="20"
                        height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21">
                        </path>
                    </svg>
                </button>
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
</div>

<script>
    function toggleBundlePassword() {
        const passwordInput = document.getElementById('bundle-password');
        const eyeIcon = document.getElementById('bundle-password-eye');
        const eyeSlashIcon = document.getElementById('bundle-password-eye-slash');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.classList.add('hidden');
            eyeSlashIcon.classList.remove('hidden');
        } else {
            passwordInput.type = 'password';
            eyeIcon.classList.remove('hidden');
            eyeSlashIcon.classList.add('hidden');
        }
    }
</script>
@endsection
