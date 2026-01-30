@extends('layout.guest')
@seo([
        'title' => 'Forgot Password - ' . config('app.name'),
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
      <h1 class="block text-lg font-bold text-gray-800">Forgot password?</h1>
      <p class="mt-2 text-sm text-gray-600">
        Remember your password?
        <a href="{{route('auth.login')}}" class="text-indigo-600 decoration-2 hover:underline focus:outline-hidden focus:underline font-medium">
          Sign in here
        </a>
      </p>
    </div>

    <div class="mt-5">
      <!-- Form -->
      <form action="{{route('auth.reset-password')}}" method="post">
        @csrf
        <div class="grid gap-y-4">
        @error('email')
          <div class="alert alert-danger text-red-500">{{ $message }}</div>
        @enderror
          <!-- Form Group -->
          <div>
            <label for="email" class="block text-sm mb-2">Email address</label>
            <div class="relative">
              <input type="email" id="email" name="email" value="{{ old('email') }}" class="py-2.5 sm:py-3 px-4 block w-full border-gray-200 rounded-lg sm:text-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:opacity-50 disabled:pointer-events-none @error('email') is-invalid @enderror" required aria-describedby="email-error">
              <div class="hidden absolute inset-y-0 end-0 pointer-events-none pe-3">
                <svg class="size-5 text-red-500" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                  <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
                </svg>
              </div>
            </div>
            <p class="hidden text-xs text-red-600 mt-2" id="email-error">Please include a valid email address so we can get back to you</p>
          </div>
          <!-- End Form Group -->

          <button type="submit" class="w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-indigo-600 text-white hover:bg-indigo-700 focus:outline-hidden focus:bg-indigo-700 disabled:opacity-50 disabled:pointer-events-none">Reset password</button>
        </div>
      </form>
      <!-- End Form -->
    </div>
  </div>
</div>
@endsection