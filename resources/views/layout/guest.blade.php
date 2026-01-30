<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <x-seo::meta />
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{config('app.name')}}</title>
    <link rel="shortcut icon" href="{{ asset('images/logo-1.png') }}" type="image/png" />
    @seo([
        'title' => config('app.name'),
        'description' => 'LinkedIn Lead Generation And Sales Automation Tool',
        'image' => asset('images/site-image.png'),
        'site_name' => config('app.name'),
        'favicon' => asset('images/logo-1.png'),
    ])

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- @notifyCss -->
    <style type="text/css">
        .notify{
            z-index: 1001 !important;
        }
        
        /* Hide collapsed logo on guest pages - only show full logo - STRONG RULE */
        img.logo-collapsed {
            display: none !important;
        }
        
        img.logo-full {
            display: block;
        }
    </style>
</head>
<body class="antialiased">
    <div class="bg-gray-100 flex h-screen items-center py-10">
        <div class="w-full max-w-md mx-auto p-6">
            <div class="flex flex-col items-center">
                <div class="flex justify-center">
                    <x-app-logo/>
                </div>
                <p class="text-sm text-gray-600 mt-2 font-medium">A platform for building something big on LinkedIn</p>
            </div>
            @yield('content')
        </div>
    </div>
    @include('notify::components.notify')

    @notifyJs
</body>
</html>