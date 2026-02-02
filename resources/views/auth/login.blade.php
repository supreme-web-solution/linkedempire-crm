@extends('layout.guest')
@seo([
    'title' => 'Login - ' . config('app.name'),
    'description' => 'LinkedIn Lead Generation And Sales Automation Tool',
    'image' => asset('images/site-image.png'),
    'site_name' => config('app.name'),
    'favicon' => asset('images/logo-1.png'),
])

@section('content')
    <div class="mt-7 bg-white border border-gray-200 rounded-xl shadow-2xs">
        <div class="p-4 sm:p-7">
            <div class="flex justify-center">
                <!-- <x-app-logo/> -->
            </div>
            <div class="text-center">
                <h1 class="block text-lg font-bold text-gray-900">Welcome</h1>
            </div>

            <div class="mt-5">
                <!-- Form -->
                <form method="post" action="{{ route('auth.authenticate') }}">
                    @csrf
                    <div class="grid gap-y-4">
                        <!-- Form Group -->
                        <div>
                            <label for="email" class="block text-sm mb-2 text-gray-700 font-bold">Email address</label>
                            <div class="relative">
                                <input type="email" id="email" name="email" value="{{ old('email') }}"
                                    class="py-2.5 sm:py-3 px-4 block w-full border-gray-200 rounded-lg sm:text-sm focus:border-[#0077b5] focus:ring-[#0077b5] disabled:opacity-50 disabled:pointer-events-none"
                                    required aria-describedby="email-error" placeholder="your@email.com">
                                <div class="hidden absolute inset-y-0 end-0 pointer-events-none pe-3">
                                    <svg class="size-5 text-red-500" width="16" height="16" fill="currentColor"
                                        viewBox="0 0 16 16" aria-hidden="true">
                                        <path
                                            d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z" />
                                    </svg>
                                </div>
                            </div>
                            <p class="hidden text-xs text-red-600 mt-2" id="email-error">Please include a valid email
                                address so we can get back to you</p>
                        </div>
                        <!-- End Form Group -->

                        <!-- Form Group -->
                        <div>
                            <div class="flex flex-wrap justify-between items-center gap-2">
                                <label for="password" class="block text-sm mb-2 text-gray-700 font-bold">Password</label>
                                <a href="{{ route('auth.forgot-password') }}"
                                    class="inline-flex items-center gap-x-1 text-sm text-[#0077b5] decoration-2 hover:text-[#005885] hover:underline focus:outline-hidden focus:underline font-medium">Forgot
                                    password?</a>
                            </div>
                            <div class="relative">
                                <input type="password" id="password" name="password"
                                    class="py-2.5 sm:py-3 px-4 pe-10 block w-full border-gray-200 rounded-lg sm:text-sm focus:border-[#0077b5] focus:ring-[#0077b5] disabled:opacity-50 disabled:pointer-events-none"
                                    required aria-describedby="password-error" placeholder="********">
                                <button type="button" onclick="togglePassword()"
                                    class="absolute inset-y-0 end-0 flex items-center pe-3 cursor-pointer hover:text-[#0077b5] transition-colors"
                                    aria-label="Toggle password visibility">
                                    <svg id="password-eye" class="size-5 text-gray-400" width="20" height="20"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                        </path>
                                    </svg>
                                    <svg id="password-eye-slash" class="size-5 text-gray-400 hidden" width="20"
                                        height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21">
                                        </path>
                                    </svg>
                                </button>
                            </div>
                            <p class="hidden text-xs text-red-600 mt-2" id="password-error">8+ characters required</p>
                        </div>
                        <!-- End Form Group -->

                        <!-- Checkbox -->
                        <div class="flex items-center">
                            <div class="flex">
                                <input id="remember-me" name="remember" type="checkbox"
                                    class="shrink-0 mt-0.5 border-gray-200 rounded-sm focus:ring-[#0077b5]"
                                    style="accent-color: #0077b5;">
                            </div>
                            <div class="ms-3">
                                <label for="remember-me" class="text-sm text-gray-700">Remember me</label>
                            </div>
                        </div>
                        <!-- End Checkbox -->

                        <button type="submit"
                            class="w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent text-white transition-all disabled:opacity-50 disabled:pointer-events-none"
                            style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);"
                            onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';"
                            onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">Sign
                            In</button>
                    </div>
                </form>
                <!-- End Form -->
            </div>
        </div>
    </div>
    <p class="text-sm text-gray-600 mt-4 text-center">Don't have an account? Contact your administrator for access.</p>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('password-eye');
            const eyeSlashIcon = document.getElementById('password-eye-slash');

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
