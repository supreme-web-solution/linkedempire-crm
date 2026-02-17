<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <x-seo::meta />
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{config('app.name')}}</title>
    {{-- <link rel="shortcut icon" href="{{ asset('images/linkdominator-48.png') }}" type="image/png" /> --}}
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <!-- @notifyCss -->
    <style type="text/css">
        :root {
            --social-toast-offset: 0px;
        }

        body.has-social-toast {
            padding-top: var(--social-toast-offset);
        }

        body.has-social-toast header {
            top: var(--social-toast-offset);
        }

        body.has-social-toast #hs-application-sidebar {
            top: var(--social-toast-offset);
            height: calc(100% - var(--social-toast-offset));
        }

        .notify{
            z-index: 1001 !important;
        }
        
        /* 🔥 Collapsible Sidebar Styles */
        #hs-application-sidebar {
            width: 16rem !important; /* 256px - expanded */
            transition: width 0.3s ease;
        }
        
        /* On mobile, always show full sidebar */
        @media (max-width: 1023px) {
            #hs-application-sidebar {
                width: 16rem !important; /* Always full on mobile */
            }
            
            #hs-application-sidebar.sidebar-collapsed {
                width: 16rem !important; /* Force full width on mobile */
            }
            
            .sidebar-collapsed .sidebar-text {
                display: block !important; /* Always show text on mobile */
                opacity: 1 !important;
                visibility: visible !important;
            }
            
            .sidebar-collapsed nav a {
                justify-content: flex-start !important; /* Full layout on mobile */
                padding-left: 0.625rem !important;
                padding-right: 0.625rem !important;
            }
        }
        
        /* Desktop collapsed state */
        @media (min-width: 1024px) {
            #hs-application-sidebar.sidebar-collapsed {
                width: 4.5rem !important; /* 72px - icon only */
                padding-right: 0.75rem !important; /* Add right padding so items don't touch body */
            }
        }
        
        /* Hide ALL text in collapsed state */
        .sidebar-collapsed .sidebar-text {
            display: none !important;
            opacity: 0;
            visibility: hidden;
        }
        
        /* Reduce nav padding when collapsed for better balance */
        .sidebar-collapsed nav {
            padding: 0.5rem !important; /* Reduced from p-4 to p-2 for better balance */
        }
        
        /* Center items perfectly in collapsed state */
        .sidebar-collapsed nav a {
            justify-content: center !important;
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
            margin: 0 auto !important; /* Ensure perfect centering */
        }
        
        /* Fix active indicator in collapsed state - keep left border visible */
        .sidebar-collapsed nav a.bg-gray-100 {
            width: auto !important;
            position: relative;
        }
        
        /* Ensure active indicator bar is visible when collapsed */
        .sidebar-collapsed nav a.bg-gray-100::before {
            left: 0 !important;
            height: 50% !important; /* Slightly shorter for better balance */
        }
        
        /* Adjust header padding */
        @media (min-width: 1024px) {
            header {
                padding-left: 4.5rem; /* Start with collapsed */
                transition: padding-left 0.3s ease;
            }
            
            header.sidebar-expanded {
                padding-left: 16rem;
            }
        }
        
        /* Adjust content area padding */
        @media (min-width: 1024px) {
            .main-content {
                padding-left: 4.5rem; /* Start with collapsed */
                transition: padding-left 0.3s ease;
            }
            
            .main-content.sidebar-expanded {
                padding-left: 16rem;
            }
        }
        
        /* Logo adjustments */
        .sidebar-logo {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .sidebar-collapsed .sidebar-logo {
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
            padding-top: 1rem !important;
            padding-bottom: 1rem !important;
            justify-content: center;
            margin: 0 auto;
        }
        
        /* Default: Hide collapsed logo everywhere by default - STRONG RULE */
        img.logo-collapsed {
            display: none !important;
        }
        
        /* Default: Show full logo everywhere */
        img.logo-full {
            display: block;
        }
        
        /* Only in sidebar: Show full logo when expanded */
        .sidebar-logo img.logo-full {
            display: block;
        }
        
        .sidebar-logo img.logo-collapsed {
            display: none !important;
        }
        
        /* Only in collapsed sidebar: Show collapsed logo, hide full logo */
        .sidebar-collapsed .sidebar-logo img.logo-full {
            display: none !important;
        }
        
        .sidebar-collapsed .sidebar-logo img.logo-collapsed {
            display: block !important;
        }
        
        .sidebar-collapsed .sidebar-logo svg,
        .sidebar-collapsed .sidebar-logo img {
            max-width: 40px;
        }
        
        /* Tooltip for collapsed state */
        .sidebar-collapsed nav a {
            position: relative;
        }
        
        .sidebar-collapsed nav a:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            margin-left: 0.75rem;
            padding: 0.5rem 0.75rem;
            background-color: #333;
            color: white;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            white-space: nowrap;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0, 119, 181, 0.3);
            pointer-events: none;
        }
        
        /* Modern Sidebar Styling */
        #hs-application-sidebar {
            background: linear-gradient(180deg, #ffffff 0%, #fafbfc 100%);
            border-right: 1px solid rgba(0, 119, 181, 0.1);
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.02);
        }
        
        #hs-application-sidebar nav a {
            position: relative;
            transition: all 0.2s ease;
            margin: 0.25rem 0.5rem;
            border-radius: 0.75rem;
        }
        
        /* Sidebar hover states */
        #hs-application-sidebar nav a:hover {
            background: linear-gradient(135deg, rgba(0, 119, 181, 0.08) 0%, rgba(0, 88, 133, 0.05) 100%);
            color: #0077b5;
            transform: translateX(2px);
        }
        
        /* Active state with gradient accent */
        #hs-application-sidebar nav a.bg-gray-100 {
            background: linear-gradient(135deg, rgba(0, 119, 181, 0.12) 0%, rgba(0, 88, 133, 0.08) 100%);
            color: #0077b5;
            font-weight: 600;
            border-left: 3px solid transparent;
            padding-left: calc(0.625rem - 3px);
            box-shadow: 0 2px 4px rgba(0, 119, 181, 0.08);
        }
        
        #hs-application-sidebar nav a.bg-gray-100::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 60%;
            background: linear-gradient(135deg, rgb(0, 119, 181) 0%, rgb(0, 88, 133) 100%);
            border-radius: 0 3px 3px 0;
        }
        
        /* Icon styling */
        #hs-application-sidebar nav a svg {
            transition: all 0.2s ease;
        }
        
        #hs-application-sidebar nav a:hover svg {
            transform: scale(1.1);
        }
        
        #hs-application-sidebar nav a.bg-gray-100 svg {
            color: #0077b5;
        }
        
        /* Logo area styling */
        .sidebar-logo {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid rgba(0, 119, 181, 0.1);
            margin-bottom: 0.5rem;
        }
        
        /* Hide sidebar text overflow */
        .sidebar-collapsed nav {
            overflow: hidden;
        }
    </style>
</head>
<body class="antialiased">
    <!-- Social Account Credentials Reminder Toast -->
    <div id="socialAccountReminderToast" class="hidden fixed top-0 left-0 right-0 z-[9999] text-white shadow-lg" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center flex-1">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-3 flex-shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                    <p class="text-sm sm:text-base font-medium flex-1">
                        <span class="hidden sm:inline">Don't forget to add your LinkedIn credentials to connect your social account. </span>
                        <span class="sm:hidden">Add your LinkedIn credentials. </span>
                        <a href="{{ route('social-account.index') }}" class="underline font-semibold hover:text-blue-100 ml-1 transition-colors">Go to Social Accounts →</a>
                    </p>
                </div>
                <button type="button" id="dismissSocialAccountReminder" class="ml-4 flex-shrink-0 text-white hover:text-blue-100 focus:outline-none focus:text-blue-100 transition-colors">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span class="sr-only">Close</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ========== HEADER ========== -->
    <header id="mainHeader" class="sticky top-0 inset-x-0 flex flex-wrap md:justify-start md:flex-nowrap z-48 w-full bg-white border-b border-gray-200 text-sm py-2.5 lg:ps-65 transition-all duration-300">
    <nav class="px-4 sm:px-6 flex basis-full items-center w-full mx-auto">
        <!-- 🔥 NEW: Sidebar Toggle Button -->
        <button type="button" 
                id="sidebarToggle"
                class="me-4 p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors hidden lg:block"
                onclick="toggleSidebar()">
            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
        </button>
        
        <div class="me-5 lg:me-0 lg:hidden">
        <!-- Logo - Using same logo as desktop -->
        <x-app-logo/>
        <!-- End Logo -->

        <div class="lg:hidden ms-1">

        </div>
        </div>

        <div class="w-full flex items-center justify-end ms-auto md:justify-between gap-x-1 md:gap-x-3">

        <div class="hidden md:block">
            <!-- Search Input -->
            <div class="relative">
            <!-- <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none z-20 ps-3.5">
                <svg class="shrink-0 size-4 text-gray-400/60" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </div>
            <input type="text" class="py-2 ps-10 pe-16 block w-full bg-white border-gray-200 rounded-lg text-sm focus:outline-hidden focus:border-blue-500 focus:ring-blue-500 checked:border-blue-500 disabled:opacity-50 disabled:pointer-events-none" placeholder="Search"> -->
            <div class="hidden absolute inset-y-0 end-0 flex items-center pointer-events-none z-20 pe-1">
                <!-- <button type="button" class="inline-flex shrink-0 justify-center items-center size-6 rounded-full text-gray-500 hover:text-blue-600 focus:outline-hidden focus:text-blue-600" aria-label="Close">
                <span class="sr-only">Close</span>
                <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
                </button> -->
            </div>
            <div class="absolute inset-y-0 end-0 flex items-center pointer-events-none z-20 pe-3 text-gray-400">
                <!-- <svg class="shrink-0 size-3 text-gray-400/60" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6v12a3 3 0 1 0 3-3H6a3 3 0 1 0 3 3V6a3 3 0 1 0-3 3h12a3 3 0 1 0-3-3"/></svg>
                <span class="mx-1">
                <svg class="shrink-0 size-3 text-gray-400/60" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                </span>
                <span class="text-xs">/</span> -->
            </div>
            </div>
            <!-- End Search Input -->
        </div>

        <div class="flex flex-row items-center justify-end gap-4">
            <!-- <button type="button" class="md:hidden size-9.5 relative inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none">
            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <span class="sr-only">Search</span>
            </button> -->

            <!-- <button type="button" class="size-9.5 relative inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none">
            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
            <span class="sr-only">Notifications</span>
            </button> -->

            <!-- <button type="button" class="size-9.5 relative inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none">
            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
            <span class="sr-only">Activity</span>
            </button> -->
            <button 
                type="button"
                class="w-full px-4 py-2 text-sm font-medium 
                leading-5 text-center text-white transition-all duration-150 
                border border-transparent rounded-lg"
                style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);"
                onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';"
                onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';"
                aria-haspopup="dialog" aria-expanded="false" aria-controls="hs-scale-animation-modal-started" data-hs-overlay="#hs-scale-animation-modal-started">
                    Get Started
            </button>

            <!-- Dropdown -->
            <div class="hs-dropdown [--placement:bottom-right] relative inline-flex">
            <button id="hs-dropdown-account" type="button" class="size-9.5 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-full border border-transparent text-gray-800 focus:outline-hidden disabled:opacity-50 disabled:pointer-events-none" aria-haspopup="menu" aria-expanded="false" aria-label="Dropdown">
                <!-- <img class="shrink-0 size-9.5 rounded-full" src="https://images.unsplash.com/photo-1568602471122-7832951cc4c5?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=facearea&facepad=2&w=320&h=320&q=80" alt="Avatar"> -->
                <img class="h-10 w-10 rounded-full" src="https://ui-avatars.com/api/?name={{auth()->user()->name}}&background=F3F4F6" alt="Avatar">
            </button>

            <div class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-60 bg-white shadow-md rounded-lg mt-2 after:h-4 after:absolute after:-bottom-4 after:start-0 after:w-full before:h-4 before:absolute before:-top-4 before:start-0 before:w-full" role="menu" aria-orientation="vertical" aria-labelledby="hs-dropdown-account">
                <div class="py-3 px-5 bg-gray-100 rounded-t-lg">
                <p class="text-sm text-gray-500">Signed in as</p>
                <p class="text-sm font-medium text-gray-800">{{ auth()->user()->email }}</p>
                </div>
                <div class="p-1.5 space-y-0.5">
                    <a href="{{route('auth.profile')}}" class="{{ Route::current()->getName() == 'auth.profile' ? 'bg-gray-100':'' }} flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">
                        <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        Profile
                    </a>
                    <a href="{{route('social-account.index')}}" class="{{ Route::current()->getName() == 'social-account.index' ? 'bg-gray-100':'' }} flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="shrink-0 size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        Social Accounts
                    </a>
                    <form action="{{route('auth.logout')}}" method="post">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="24" height="24" class="shrink-0 size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9" />
                            </svg>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
            </div>
            <!-- End Dropdown -->
        </div>
        </div>
    </nav>
    </header>
    <!-- ========== END HEADER ========== -->

    <!-- ========== MAIN CONTENT ========== -->
    <div class="-mt-px">
    <!-- Breadcrumb -->
    <div class="sticky top-0 inset-x-0 z-20 bg-white border-y border-gray-200 px-4 sm:px-6 lg:px-8 lg:hidden">
        <div class="flex items-center py-2">
        <!-- Navigation Toggle -->
        <button type="button" class="size-8 flex justify-center items-center gap-x-2 border border-gray-200 text-gray-800 hover:text-gray-500 rounded-lg focus:outline-hidden focus:text-gray-500 disabled:opacity-50 disabled:pointer-events-none" aria-haspopup="dialog" aria-expanded="false" aria-controls="hs-application-sidebar" aria-label="Toggle navigation" data-hs-overlay="#hs-application-sidebar">
            <span class="sr-only">Toggle Navigation</span>
            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M15 3v18"/><path d="m8 9 3 3-3 3"/></svg>
        </button>
        <!-- End Navigation Toggle -->

        <!-- Breadcrumb -->
        <ol class="ms-3 flex items-center whitespace-nowrap">
            <li class="flex items-center text-sm text-gray-800">
            Application Layout
            <svg class="shrink-0 mx-3 overflow-visible size-2.5 text-gray-400" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 1L10.6869 7.16086C10.8637 7.35239 10.8637 7.64761 10.6869 7.83914L5 14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            </li>
            <li class="text-sm font-semibold text-gray-800 truncate" aria-current="page">
            Dashboard
            </li>
        </ol>
        <!-- End Breadcrumb -->
        </div>
    </div>
    <!-- End Breadcrumb -->
    </div>

    <!-- 🔥 UPDATED: Collapsible Sidebar -->
    <div id="hs-application-sidebar" class="hs-overlay [--auto-close:lg]
    hs-overlay-open:translate-x-0
    -translate-x-full transition-all duration-300 transform
    h-full
    hidden
    fixed inset-y-0 start-0 z-60
    bg-white border-e border-gray-200
    lg:block lg:translate-x-0 lg:end-auto lg:bottom-0" role="dialog" tabindex="-1" aria-label="Sidebar">
    <div class="relative flex flex-col h-full max-h-full">
        <div class="px-6 pt-6 pb-4 flex items-center justify-center sidebar-logo">
        <!-- Logo -->
        <!-- <a class="flex-none rounded-xl text-xl inline-block font-semibold focus:outline-hidden focus:opacity-80" href="#" aria-label="Preline">
            <svg class="w-28 h-auto" width="116" height="32" viewBox="0 0 116 32" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M33.5696 30.8182V11.3182H37.4474V13.7003H37.6229C37.7952 13.3187 38.0445 12.9309 38.3707 12.5369C38.7031 12.1368 39.134 11.8045 39.6634 11.5398C40.1989 11.2689 40.8636 11.1335 41.6577 11.1335C42.6918 11.1335 43.6458 11.4044 44.5199 11.946C45.3939 12.4815 46.0926 13.291 46.6158 14.3743C47.139 15.4515 47.4006 16.8026 47.4006 18.4276C47.4006 20.0095 47.1451 21.3452 46.6342 22.4347C46.1295 23.518 45.4401 24.3397 44.5661 24.8999C43.6982 25.4538 42.7256 25.7308 41.6484 25.7308C40.8852 25.7308 40.2358 25.6046 39.7003 25.3523C39.1709 25.0999 38.737 24.7829 38.3984 24.4013C38.0599 24.0135 37.8014 23.6226 37.6229 23.2287H37.5028V30.8182H33.5696ZM37.4197 18.4091C37.4197 19.2524 37.5367 19.9879 37.7706 20.6158C38.0045 21.2436 38.343 21.733 38.7862 22.0838C39.2294 22.4285 39.768 22.6009 40.402 22.6009C41.0421 22.6009 41.5838 22.4254 42.027 22.0746C42.4702 21.7176 42.8056 21.2251 43.0334 20.5973C43.2673 19.9633 43.3842 19.2339 43.3842 18.4091C43.3842 17.5904 43.2704 16.8703 43.0426 16.2486C42.8149 15.6269 42.4794 15.1406 42.0362 14.7898C41.593 14.4389 41.0483 14.2635 40.402 14.2635C39.7618 14.2635 39.2202 14.4328 38.777 14.7713C38.34 15.1098 38.0045 15.59 37.7706 16.2116C37.5367 16.8333 37.4197 17.5658 37.4197 18.4091ZM49.2427 25.5V11.3182H53.0559V13.7926H53.2037C53.4622 12.9124 53.8961 12.2476 54.5055 11.7983C55.1149 11.3428 55.8166 11.1151 56.6106 11.1151C56.8076 11.1151 57.02 11.1274 57.2477 11.152C57.4754 11.1766 57.6755 11.2105 57.8478 11.2536V14.7436C57.6632 14.6882 57.4077 14.639 57.0815 14.5959C56.7553 14.5528 56.4567 14.5312 56.1859 14.5312C55.6073 14.5312 55.0903 14.6574 54.6348 14.9098C54.1854 15.156 53.8284 15.5007 53.5638 15.9439C53.3052 16.3871 53.176 16.898 53.176 17.4766V25.5H49.2427ZM64.9043 25.777C63.4455 25.777 62.1898 25.4815 61.1373 24.8906C60.0909 24.2936 59.2845 23.4503 58.7182 22.3608C58.1519 21.2652 57.8688 19.9695 57.8688 18.4737C57.8688 17.0149 58.1519 15.7346 58.7182 14.6328C59.2845 13.531 60.0816 12.6723 61.1096 12.0568C62.1437 11.4413 63.3563 11.1335 64.7474 11.1335C65.683 11.1335 66.5539 11.2843 67.3603 11.5859C68.1728 11.8814 68.8806 12.3277 69.4839 12.9247C70.0932 13.5218 70.5672 14.2727 70.9057 15.1776C71.2443 16.0762 71.4135 17.1288 71.4135 18.3352V19.4155H59.4384V16.978H67.7111C67.7111 16.4117 67.588 15.91 67.3418 15.473C67.0956 15.036 66.754 14.6944 66.317 14.4482C65.8861 14.1958 65.3844 14.0696 64.812 14.0696C64.2149 14.0696 63.6856 14.2081 63.2239 14.4851C62.7684 14.7559 62.4114 15.1222 62.1529 15.5838C61.8944 16.0393 61.762 16.5471 61.7559 17.1072V19.4247C61.7559 20.1264 61.8851 20.7327 62.1437 21.2436C62.4083 21.7545 62.7807 22.1484 63.2608 22.4254C63.741 22.7024 64.3103 22.8409 64.9689 22.8409C65.406 22.8409 65.8061 22.7794 66.1692 22.6562C66.5324 22.5331 66.8432 22.3485 67.1018 22.1023C67.3603 21.8561 67.5572 21.5545 67.6927 21.1974L71.3304 21.4375C71.1458 22.3116 70.7672 23.0748 70.1948 23.7273C69.6285 24.3736 68.896 24.8783 67.9974 25.2415C67.1048 25.5985 66.0738 25.777 64.9043 25.777ZM77.1335 6.59091V25.5H73.2003V6.59091H77.1335ZM79.5043 25.5V11.3182H83.4375V25.5H79.5043ZM81.4801 9.49006C80.8954 9.49006 80.3937 9.29616 79.9752 8.90838C79.5628 8.51444 79.3566 8.04356 79.3566 7.49574C79.3566 6.95407 79.5628 6.48935 79.9752 6.10156C80.3937 5.70762 80.8954 5.51065 81.4801 5.51065C82.0649 5.51065 82.5635 5.70762 82.9759 6.10156C83.3944 6.48935 83.6037 6.95407 83.6037 7.49574C83.6037 8.04356 83.3944 8.51444 82.9759 8.90838C82.5635 9.29616 82.0649 9.49006 81.4801 9.49006ZM89.7415 17.3011V25.5H85.8083V11.3182H89.5569V13.8203H89.723C90.037 12.9955 90.5632 12.343 91.3019 11.8629C92.0405 11.3767 92.9361 11.1335 93.9887 11.1335C94.9735 11.1335 95.8322 11.349 96.5647 11.7798C97.2971 12.2107 97.8665 12.8262 98.2728 13.6264C98.679 14.4205 98.8821 15.3684 98.8821 16.4702V25.5H94.9489V17.1719C94.9551 16.304 94.7335 15.6269 94.2841 15.1406C93.8348 14.6482 93.2162 14.402 92.4283 14.402C91.8989 14.402 91.4311 14.5159 91.0249 14.7436C90.6248 14.9714 90.3109 15.3037 90.0831 15.7408C89.8615 16.1716 89.7477 16.6918 89.7415 17.3011ZM107.665 25.777C106.206 25.777 104.951 25.4815 103.898 24.8906C102.852 24.2936 102.045 23.4503 101.479 22.3608C100.913 21.2652 100.63 19.9695 100.63 18.4737C100.63 17.0149 100.913 15.7346 101.479 14.6328C102.045 13.531 102.842 12.6723 103.87 12.0568C104.905 11.4413 106.117 11.1335 107.508 11.1335C108.444 11.1335 109.315 11.2843 110.121 11.5859C110.934 11.8814 111.641 12.3277 112.245 12.9247C112.854 13.5218 113.328 14.2727 113.667 15.1776C114.005 16.0762 114.174 17.1288 114.174 18.3352V19.4155H102.199V16.978H110.472C110.472 16.4117 110.349 15.91 110.103 15.473C109.856 15.036 109.515 14.6944 109.078 14.4482C108.647 14.1958 108.145 14.0696 107.573 14.0696C106.976 14.0696 106.446 14.2081 105.985 14.4851C105.529 14.7559 105.172 15.1222 104.914 15.5838C104.655 16.0393 104.523 16.5471 104.517 17.1072V19.4247C104.517 20.1264 104.646 20.7327 104.905 21.2436C105.169 21.7545 105.542 22.1484 106.022 22.4254C106.502 22.7024 107.071 22.8409 107.73 22.8409C108.167 22.8409 108.567 22.7794 108.93 22.6562C109.293 22.5331 109.604 22.3485 109.863 22.1023C110.121 21.8561 110.318 21.5545 110.454 21.1974L114.091 21.4375C113.907 22.3116 113.528 23.0748 112.956 23.7273C112.389 24.3736 111.657 24.8783 110.758 25.2415C109.866 25.5985 108.835 25.777 107.665 25.777Z" class="fill-blue-600" fill="currentColor"/>
            <path d="M1 29.5V16.5C1 9.87258 6.37258 4.5 13 4.5C19.6274 4.5 25 9.87258 25 16.5C25 23.1274 19.6274 28.5 13 28.5H12" class="stroke-blue-600" stroke="currentColor" stroke-width="2"/>
            <path d="M5 29.5V16.66C5 12.1534 8.58172 8.5 13 8.5C17.4183 8.5 21 12.1534 21 16.66C21 21.1666 17.4183 24.82 13 24.82H12" class="stroke-blue-600" stroke="currentColor" stroke-width="2"/>
            <circle cx="13" cy="16.5214" r="5" class="fill-blue-600" fill="currentColor"/>
            </svg>
        </a> -->
        <x-app-logo/>
        <!-- End Logo -->

        <div class="hidden lg:block ms-2">

        </div>
        </div>

        <!-- Content -->
        <div class="h-full overflow-y-auto [&::-webkit-scrollbar]:w-2 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-track]:bg-gray-100 [&::-webkit-scrollbar-thumb]:bg-gray-300">
        <nav class="hs-accordion-group p-4 w-full flex flex-col flex-wrap" data-hs-accordion-always-open>
            <ul class="flex flex-col space-y-0.5">
                @can('FE')
                <li>
                    <a href="{{route('dashboard')}}" data-tooltip="Dashboard" class="flex items-center justify-start gap-x-3.5 py-2 px-2.5 text-sm text-gray-800 rounded-lg hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 {{ Route::current()->getName() == 'dashboard' ? 'bg-gray-100':'' }}">
                        <svg class="shrink-0 size-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        <span class="sidebar-text">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('competitor-followers.index') }}" data-tooltip="{{ __('competitor_followers.title') }}" class="w-full flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-gray-800 rounded-lg hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 {{ in_array(Route::current()->getName(), ['competitor-followers.index','competitor-followers.show']) ? 'bg-gray-100':'' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                          </svg>
                          
                        <span class="sidebar-text">{{ __('competitor_followers.title') }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{route('aiwriter.index')}}" data-tooltip="Ai Messages" class="w-full flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-gray-800 rounded-lg hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 {{ in_array(Route::current()->getName(), ['aiwriter.index','aiwriter.create','aiwriter.edit']) ? 'bg-gray-100':'' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                        </svg>
                        <span class="sidebar-text">AI Messages</span>
                    </a>
                </li>
                <li>
                    <a href="{{route('content-creator.index')}}" data-tooltip="AI Content Creator" class="w-full flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-gray-800 rounded-lg hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 {{ in_array(Route::current()->getName(), ['content-creator.index','content-creator.create','content-creator.edit']) ? 'bg-gray-100':'' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                        </svg>
                        <span class="sidebar-text">AI Content Creation</span>
                    </a>
                </li>
                <li>
                    <a href="{{route('inspiration.index')}}" data-tooltip="💡 Inspiration" class="w-full flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-gray-800 rounded-lg hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 {{ in_array(Route::current()->getName(), ['inspiration.index']) ? 'bg-gray-100':'' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18"/>
                        </svg>
                        <span class="sidebar-text">Inspiration</span>
                    </a>
                </li>
             
                <li>
                    <a href="{{route('leads.list')}}" data-tooltip="Leads" class="w-full flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-gray-800 rounded-lg hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 {{ in_array(Route::current()->getName(), ['leads.list','leads.show']) ? 'bg-gray-100':'' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="shrink-0 size-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                        </svg>
                        <span class="sidebar-text">Leads</span>
                    </a>
                </li>
                <li>
                    <a href="{{route('campaign')}}" data-tooltip="Campaign" class="w-full flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-gray-800 rounded-lg hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 {{ in_array(Route::current()->getName(), ['campaign','campaign.create']) ? 'bg-gray-100':'' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="shrink-0 size-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5" />
                        </svg>
                        <span class="sidebar-text">Campaign</span>
                    </a>
                </li>
                <li>
                    <a href="{{route('calls')}}" data-tooltip="Call Manager" class="w-full flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-gray-800 rounded-lg hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 {{ in_array(Route::current()->getName(), ['calls','calls.reminders']) ? 'bg-gray-100':'' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="shrink-0 size-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                        </svg>
                        <span class="sidebar-text">Call Manager</span>
                    </a>
                </li>
                {{-- <li>
                    <a href="{{route('post.index')}}" data-tooltip="Schedule Post" class="w-full flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-gray-800 rounded-lg hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 {{ in_array(Route::current()->getName(), ['post.index','post.create','post.edit']) ? 'bg-gray-100':'' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z"/>
                        </svg>
                        <span class="sidebar-text">Schedule Post</span>
                    </a>
                </li> --}}
                @endcan
                <li>
                    <a href="{{route('tutorials')}}" data-tooltip="Tutorials" class="w-full flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-gray-800 rounded-lg hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 {{ in_array(Route::current()->getName(), ['tutorials']) ? 'bg-gray-100':'' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25"/>
                        </svg>
                        <span class="sidebar-text">Tutorials</span>
                    </a>
                </li>
                @if(auth()->user()->can('OTO8') || auth()->user()->can('Bundle'))
                <li>
                    <a href="{{route('team.index',['tab' => 'members'])}}" data-tooltip="Team" class="w-full flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-gray-800 rounded-lg hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 {{ in_array(Route::current()->getName(), ['team.index']) ? 'bg-gray-100':'' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
                        </svg>
                        <span class="sidebar-text">Team</span>
                    </a>
                </li>
                @endif
                {{-- <li>
                    <a href="{{route('comment.index',['tab' => 'feeds'])}}" data-tooltip="Comment Feeds" class="w-full flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-gray-800 rounded-lg hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 {{ in_array(Route::current()->getName(), ['comment.index','comment.create-campaign']) ? 'bg-gray-100':'' }}">
                        <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11c.889-.086 1.416-.543 2.156-1.057a22.323 22.323 0 0 0 3.958-5.084 1.6 1.6 0 0 1 .582-.628 1.549 1.549 0 0 1 1.466-.087c.205.095.388.233.537.406a1.64 1.64 0 0 1 .384 1.279l-1.388 4.114M7 11H4v6.5A1.5 1.5 0 0 0 5.5 19v0A1.5 1.5 0 0 0 7 17.5V11Zm6.5-1h4.915c.286 0 .372.014.626.15.254.135.472.332.637.572a1.874 1.874 0 0 1 .215 1.673l-2.098 6.4C17.538 19.52 17.368 20 16.12 20c-2.303 0-4.79-.943-6.67-1.475"/>
                        </svg>
                        <span class="sidebar-text">Comment Feeds</span>
                    </a>
                </li>
                <li>
                    <a href="{{route('auto-comment.index')}}" data-tooltip="AI Auto Comments" class="w-full flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-gray-800 rounded-lg hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 {{ in_array(Route::current()->getName(), ['auto-comment.index','auto-comment.preferences']) ? 'bg-gray-100':'' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z"/>
                        </svg>
                        <span class="sidebar-text">AI Auto Comments</span>
                    </a>
                </li> --}}
                @if(auth()->user()->can('OTO2') || auth()->user()->can('OTO8') || auth()->user()->can('Bundle'))
                <li>
                    <a href="{{route('upsell-unlimited')}}" data-tooltip="Upsell Unlimited" class="w-full flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-gray-800 rounded-lg hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 {{ in_array(Route::current()->getName(), ['upsell-unlimited']) ? 'bg-gray-100':'' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5" >
                        <path d="M18.375 2.25c-1.035 0-1.875.84-1.875 1.875v15.75c0 1.035.84 1.875 1.875 1.875h.75c1.035 0 1.875-.84 1.875-1.875V4.125c0-1.036-.84-1.875-1.875-1.875h-.75zM9.75 8.625c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-.75a1.875 1.875 0 01-1.875-1.875V8.625zM3 13.125c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v6.75c0 1.035-.84 1.875-1.875 1.875h-.75A1.875 1.875 0 013 19.875v-6.75z"/>
                        </svg>
                        <span class="sidebar-text">Upsell Unlimited</span>
                    </a>
                </li>
                @endif
                @if(auth()->user()->can('OTO3') || auth()->user()->can('OTO8') || auth()->user()->can('Bundle'))
                <li>
                    <a href="{{route('market-agency-setup')}}" data-tooltip="Marketing Agency" class="w-full flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-gray-800 rounded-lg hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 {{ in_array(Route::current()->getName(), ['market-agency-setup']) ? 'bg-gray-100':'' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                            <path fill-rule="evenodd" d="M6 3.75A2.75 2.75 0 018.75 1h2.5A2.75 2.75 0 0114 3.75v.443c.572.055 1.14.122 1.706.2C17.053 4.582 18 5.75 18 7.07v3.469c0 1.126-.694 2.191-1.83 2.54-1.952.599-4.024.921-6.17.921s-4.219-.322-6.17-.921C2.694 12.73 2 11.665 2 10.539V7.07c0-1.321.947-2.489 2.294-2.676A41.047 41.047 0 016 4.193V3.75zm6.5 0v.325a41.622 41.622 0 00-5 0V3.75c0-.69.56-1.25 1.25-1.25h2.5c.69 0 1.25.56 1.25 1.25zM10 10a1 1 0 00-1 1v.01a1 1 0 001 1h.01a1 1 0 001-1V11a1 1 0 00-1-1H10z" clip-rule="evenodd" />
                            <path d="M3 15.055v-.684c.126.053.255.1.39.142 2.092.642 4.313.987 6.61.987 2.297 0 4.518-.345 6.61-.987.135-.041.264-.089.39-.142v.684c0 1.347-.985 2.53-2.363 2.686a41.454 41.454 0 01-9.274 0C3.985 17.585 3 16.402 3 15.055z"/>
                        </svg>
                        <span class="sidebar-text">Marketing Agency</span>
                    </a>
                </li>
                @endif
                @if(auth()->user()->can('OTO4') || auth()->user()->can('OTO8') || auth()->user()->can('Bundle'))
                <li>
                    <a href="{{route('dfy-campaign')}}" data-tooltip="DFY Campaign" class="w-full flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-gray-800 rounded-lg hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 {{ in_array(Route::current()->getName(), ['dfy-campaign']) ? 'bg-gray-100':'' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                            <path d="M13.92 3.845a19.361 19.361 0 01-6.3 1.98C6.765 5.942 5.89 6 5 6a4 4 0 00-.504 7.969 15.974 15.974 0 001.271 3.341c.397.77 1.342 1 2.05.59l.867-.5c.726-.42.94-1.321.588-2.021-.166-.33-.315-.666-.448-1.004 1.8.358 3.511.964 5.096 1.78A17.964 17.964 0 0015 10c0-2.161-.381-4.234-1.08-6.155zM15.243 3.097A19.456 19.456 0 0116.5 10c0 2.431-.445 4.758-1.257 6.904l-.03.077a.75.75 0 001.401.537 20.902 20.902 0 001.312-5.745 1.999 1.999 0 000-3.545 20.902 20.902 0 00-1.312-5.745.75.75 0 00-1.4.537l.029.077z"/>
                        </svg>
                        <span class="sidebar-text">DFY Campaign</span>
                    </a>
                </li>
                @endif
                {{-- @if(auth()->user()->can('OTO6') || auth()->user()->can('OTO8') || auth()->user()->can('Bundle'))
                <li>
                    <a href="{{route('dfy-software-empire-setup')}}" data-tooltip="DFY Software Empire" class="w-full flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-gray-800 rounded-lg hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 {{ in_array(Route::current()->getName(), ['dfy-software-empire-setup']) ? 'bg-gray-100':'' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                            <path fill-rule="evenodd" d="M6 3.75A2.75 2.75 0 018.75 1h2.5A2.75 2.75 0 0114 3.75v.443c.572.055 1.14.122 1.706.2C17.053 4.582 18 5.75 18 7.07v3.469c0 1.126-.694 2.191-1.83 2.54-1.952.599-4.024.921-6.17.921s-4.219-.322-6.17-.921C2.694 12.73 2 11.665 2 10.539V7.07c0-1.321.947-2.489 2.294-2.676A41.047 41.047 0 016 4.193V3.75zm6.5 0v.325a41.622 41.622 0 00-5 0V3.75c0-.69.56-1.25 1.25-1.25h2.5c.69 0 1.25.56 1.25 1.25zM10 10a1 1 0 00-1 1v.01a1 1 0 001 1h.01a1 1 0 001-1V11a1 1 0 00-1-1H10z" clip-rule="evenodd"/>
                            <path d="M3 15.055v-.684c.126.053.255.1.39.142 2.092.642 4.313.987 6.61.987 2.297 0 4.518-.345 6.61-.987.135-.041.264-.089.39-.142v.684c0 1.347-.985 2.53-2.363 2.686a41.454 41.454 0 01-9.274 0C3.985 17.585 3 16.402 3 15.055z"/>
                        </svg>
                        <span class="sidebar-text">DFY Software Empire</span>
                    </a>
                </li>
                @endif --}}
                @if(auth()->user()->can('OTO7') || auth()->user()->can('OTO8') || auth()->user()->can('Bundle'))
                <li>
                    <a href="{{route('coach-program')}}" data-tooltip="Coaching Program" class="w-full flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-gray-800 rounded-lg hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 {{ in_array(Route::current()->getName(), ['coach-program']) ? 'bg-gray-100':'' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                            <path d="M4.25 2A2.25 2.25 0 002 4.25v2a.75.75 0 001.5 0v-2a.75.75 0 01.75-.75h2a.75.75 0 000-1.5h-2zM13.75 2a.75.75 0 000 1.5h2a.75.75 0 01.75.75v2a.75.75 0 001.5 0v-2A2.25 2.25 0 0015.75 2h-2zM3.5 13.75a.75.75 0 00-1.5 0v2A2.25 2.25 0 004.25 18h2a.75.75 0 000-1.5h-2a.75.75 0 01-.75-.75v-2zM18 13.75a.75.75 0 00-1.5 0v2a.75.75 0 01-.75.75h-2a.75.75 0 000 1.5h2A2.25 2.25 0 0018 15.75v-2zM7 10a3 3 0 116 0 3 3 0 01-6 0z"/>
                        </svg>
                        <span class="sidebar-text">Coaching Program</span>
                    </a>
                </li>
                @endif
                @if(auth()->user()->can('OTO8') || auth()->user()->can('Bundle'))
                <li>
                    <a href="{{route('unlimited-traffic')}}" data-tooltip="Unlimited Traffic" class="w-full flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-gray-800 rounded-lg hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 {{ in_array(Route::current()->getName(), ['unlimited-traffic']) ? 'bg-gray-100':'' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                            <path d="M4.25 2A2.25 2.25 0 002 4.25v2a.75.75 0 001.5 0v-2a.75.75 0 01.75-.75h2a.75.75 0 000-1.5h-2zM13.75 2a.75.75 0 000 1.5h2a.75.75 0 01.75.75v2a.75.75 0 001.5 0v-2A2.25 2.25 0 0015.75 2h-2zM3.5 13.75a.75.75 0 00-1.5 0v2A2.25 2.25 0 004.25 18h2a.75.75 0 000-1.5h-2a.75.75 0 01-.75-.75v-2zM18 13.75a.75.75 0 00-1.5 0v2a.75.75 0 01-.75.75h-2a.75.75 0 000 1.5h2A2.25 2.25 0 0018 15.75v-2zM7 10a3 3 0 116 0 3 3 0 01-6 0z"/>
                        </svg>
                        <span class="sidebar-text">Unlimited Traffic</span>
                    </a>
                </li>
                @endif
                <li>
                    <a href="{{route('tutorials')}}" data-tooltip="Tutorials" class="w-full flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-gray-800 rounded-lg hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 {{ in_array(Route::current()->getName(), ['tutorials']) ? 'bg-gray-100':'' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25"/>
                        </svg>
                        <span class="sidebar-text">Tutorials</span>
                    </a>
                </li>
                @if(auth()->user()->can('OTO5') || auth()->user()->can('OTO8') || auth()->user()->can('Bundle'))
                <li>
                    <a href="{{route('reseller.index')}}" data-tooltip="Reseller" class="w-full flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-gray-800 rounded-lg hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 {{ in_array(Route::current()->getName(), ['reseller.index']) ? 'bg-gray-100':'' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/>
                        </svg>
                        <span class="sidebar-text">Reseller</span>
                    </a>
                </li>
                @endif
                {{-- @can('view_user_manager_menu') --}}
                @if(auth()->user()->email == 'admin@gmail.com' || auth()->user()->email == 'vickenconcept@gmail.com')
                <li>
                    <a href="{{route('users.index')}}" data-tooltip="Users" class="w-full flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-gray-800 rounded-lg hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 {{ in_array(Route::current()->getName(), ['users.index']) ? 'bg-gray-100':'' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/>
                        </svg>
                        <span class="sidebar-text">Users</span>
                    </a>
                </li>
                @endif
                {{-- @endcan --}}
                
            </ul>
        </nav>
        </div>
        <!-- End Content -->
    </div>
    </div>
    <!-- End Sidebar -->

    <!-- Content -->
    <div class="main-content w-full lg:ps-64 bg-gray-50 min-h-screen">
        <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">
            @yield('content')
        </div>
        <footer class="w-full max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="py-6 border-t border-gray-200">
                <div class="flex flex-wrap justify-center items-center gap-2">
                <div>
                    <p class="text-sm text-gray-600">
                    Copyright &copy; {{date('Y')}}. {{config('app.name')}} All rights reserved.
                    </p>
                </div>
                </div>
            </div>
        </footer>
    </div>
    <!-- End Content -->
    <!-- ========== END MAIN CONTENT ========== -->
    
    @include('notify::components.notify')

    @notifyJs

    <script>
    // 🔥 Social Account Credentials Reminder Toast
    (function() {
        const TOAST_DISMISSED_KEY = 'socialAccountReminderDismissed';
        const TOAST_LAST_DISMISSED_KEY = 'socialAccountReminderLastDismissed';
        const THREE_MINUTES_MS = 600 * 60 * 1000; // 2 hours in milliseconds
        const toast = document.getElementById('socialAccountReminderToast');
        const dismissBtn = document.getElementById('dismissSocialAccountReminder');
        let reminderInterval = null;
        
        if (!toast) return;
        
        function shouldShowToast() {
            // Check if user is already on the social account page
            const currentPath = window.location.pathname;
            if (currentPath === '/social-account' || currentPath.startsWith('/social-account/')) {
                return false;
            }
            
            // Check if permanently dismissed
            const dismissed = localStorage.getItem(TOAST_DISMISSED_KEY);
            if (dismissed === 'true') {
                return false;
            }
            
            // If toast is already visible, keep it visible (persists across refresh)
            if (!toast.classList.contains('hidden')) {
                return true;
            }
            
            // Check if 3 minutes have passed since last dismissed
            const lastDismissed = localStorage.getItem(TOAST_LAST_DISMISSED_KEY);
            if (!lastDismissed) {
                return true; // Never dismissed, show it
            }
            
            const timeSinceLastDismissed = Date.now() - parseInt(lastDismissed, 10);
            return timeSinceLastDismissed >= THREE_MINUTES_MS;
        }
        
        function setToastOffset(height) {
            const offset = Math.max(0, height || 0);
            document.documentElement.style.setProperty('--social-toast-offset', `${offset}px`);
            document.body.classList.toggle('has-social-toast', offset > 0);
        }

        function showToast() {
            if (shouldShowToast()) {
                toast.classList.remove('hidden');
                const height = toast.getBoundingClientRect().height;
                setToastOffset(height);
            } else {
                // Hide if conditions not met (e.g., on social account page or not enough time passed)
                toast.classList.add('hidden');
                setToastOffset(0);
            }
        }
        
        function hideToast() {
            toast.classList.add('hidden');
            setToastOffset(0);
        }
        
        function dismissToast() {
            hideToast();
            // Store when it was dismissed (not permanently)
            localStorage.setItem(TOAST_LAST_DISMISSED_KEY, Date.now().toString());
        }
        
        function permanentlyDismissToast() {
            hideToast();
            localStorage.setItem(TOAST_DISMISSED_KEY, 'true');
            // Stop the interval if permanently dismissed
            if (reminderInterval) {
                clearInterval(reminderInterval);
                reminderInterval = null;
            }
        }
        
        // Initialize toast and set up interval
        function initToast() {
            showToast();
            
            // Check every 3 minutes if toast should be shown again
            if (!reminderInterval) {
                reminderInterval = setInterval(showToast, THREE_MINUTES_MS);
            }
        }
        
        // Show toast on page load - persists across refresh if visible
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initToast);
        } else {
            initToast();
        }
        
        // Dismiss button handler - temporarily dismisses (shows again after 3 minutes)
        if (dismissBtn) {
            dismissBtn.addEventListener('click', dismissToast);
        }
        
        // Clean up interval when page unloads
        window.addEventListener('beforeunload', function() {
            if (reminderInterval) {
                clearInterval(reminderInterval);
            }
        });
    })();
    
    // 🔥 Sidebar Toggle Function (desktop only)
    function toggleSidebar() {
        const sidebar = document.getElementById('hs-application-sidebar');
        const header = document.querySelector('header');
        const content = document.querySelector('.main-content');
        
        // Don't toggle on mobile - always keep full
        const isMobile = window.innerWidth < 1024;
        if (isMobile) {
            return; // Exit early on mobile
        }
        
        // Toggle collapsed class on sidebar (desktop only)
        const isCurrentlyCollapsed = sidebar.classList.contains('sidebar-collapsed');
        
        if (isCurrentlyCollapsed) {
            // Expand
            sidebar.classList.remove('sidebar-collapsed');
            header.classList.add('sidebar-expanded');
            content.classList.add('sidebar-expanded');
        } else {
            // Collapse
            sidebar.classList.add('sidebar-collapsed');
            header.classList.remove('sidebar-expanded');
            content.classList.remove('sidebar-expanded');
        }
        
        // Save state to localStorage
        const isCollapsed = sidebar.classList.contains('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed ? 'true' : 'false');
        
        console.log('Sidebar toggled:', isCollapsed ? 'collapsed (icons only)' : 'expanded (full)');
    }
    
    // 🔥 Load sidebar state on page load - DEFAULT TO COLLAPSED (desktop only)
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarState = localStorage.getItem('sidebarCollapsed');
        const sidebar = document.getElementById('hs-application-sidebar');
        const header = document.querySelector('header');
        const content = document.querySelector('.main-content');
        
        // Check if mobile view
        const isMobile = window.innerWidth < 1024;
        
        if (isMobile) {
            // On mobile, always show full sidebar (remove collapsed class)
            sidebar.classList.remove('sidebar-collapsed');
            header.classList.remove('sidebar-expanded');
            content.classList.remove('sidebar-expanded');
        } else {
            // Desktop: DEFAULT TO COLLAPSED (small sidebar with icons only)
            if (sidebarState === null || sidebarState === 'true') {
                // Collapsed state
                sidebar.classList.add('sidebar-collapsed');
                header.classList.remove('sidebar-expanded');
                content.classList.remove('sidebar-expanded');
            } else {
                // Expanded state
                sidebar.classList.remove('sidebar-collapsed');
                header.classList.add('sidebar-expanded');
                content.classList.add('sidebar-expanded');
            }
        }
        
    });
    
    // Handle window resize to maintain full sidebar on mobile
    window.addEventListener('resize', function() {
        const sidebar = document.getElementById('hs-application-sidebar');
        const isMobile = window.innerWidth < 1024;
        
        if (isMobile) {
            // On mobile, always show full sidebar
            sidebar.classList.remove('sidebar-collapsed');
        }
    });
    
    // 🔥 FIX: Remove backdrop overlay when sidebar closes on mobile
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('hs-application-sidebar');
        
        if (!sidebar) return;
        
        // Function to remove backdrop overlays
        function removeBackdropOverlays() {
            // Remove any backdrop elements created by Preline (common class names)
            const backdropSelectors = [
                '.hs-overlay-backdrop',
                '[class*="backdrop"]',
                '.hs-overlay-backdrop-open',
                'body > .hs-overlay-backdrop',
                'body > div[class*="backdrop"]'
            ];
            
            backdropSelectors.forEach(selector => {
                try {
                    const backdrops = document.querySelectorAll(selector);
                    backdrops.forEach(backdrop => {
                        // Make sure it's not the sidebar itself
                        if (backdrop.id !== 'hs-application-sidebar') {
                            backdrop.remove();
                        }
                    });
                } catch (e) {
                    // Ignore invalid selectors
                }
            });
            
            // Also check for any fixed overlay divs that might be covering the screen
            const allDivs = document.querySelectorAll('body > div');
            allDivs.forEach(div => {
                const styles = window.getComputedStyle(div);
                const zIndex = parseInt(styles.zIndex) || 0;
                const position = styles.position;
                const bgColor = styles.backgroundColor;
                const opacity = parseFloat(styles.opacity) || 0;
                
                // Check if it's a backdrop-like element
                if (position === 'fixed' && 
                    zIndex >= 50 && 
                    zIndex < 100 && // Backdrop usually has z-index between 50-99
                    (bgColor !== 'rgba(0, 0, 0, 0)' && bgColor !== 'transparent') &&
                    div.id !== 'hs-application-sidebar' &&
                    !div.classList.contains('hs-overlay-open') &&
                    !div.querySelector('#hs-application-sidebar')) {
                    // This looks like a leftover backdrop
                    div.remove();
                }
            });
            
            // Remove any body classes that might prevent scrolling
            document.body.classList.remove('overflow-hidden');
        }
        
        // Use MutationObserver to detect when sidebar closes
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const isOpen = sidebar.classList.contains('hs-overlay-open');
                    const isHidden = sidebar.classList.contains('hidden');
                    
                    // If sidebar is closed (not open and hidden)
                    if (!isOpen && isHidden) {
                        // Small delay to ensure Preline has finished its close animation
                        setTimeout(removeBackdropOverlays, 150);
                    }
                }
            });
        });
        
        observer.observe(sidebar, {
            attributes: true,
            attributeFilter: ['class']
        });
        
        // Listen for click events outside sidebar (mobile only)
        document.addEventListener('click', function(e) {
            const isMobile = window.innerWidth < 1024;
            if (!isMobile) return;
            
            // Check if click is outside sidebar and sidebar toggle button
            const clickedSidebar = sidebar.contains(e.target);
            const clickedToggle = e.target.closest('[data-hs-overlay="#hs-application-sidebar"]');
            
            if (!clickedSidebar && !clickedToggle) {
                // Sidebar should be closing, remove backdrop after animation
                setTimeout(function() {
                    if (!sidebar.classList.contains('hs-overlay-open') || sidebar.classList.contains('hidden')) {
                        removeBackdropOverlays();
                    }
                }, 350); // Wait for close animation (300ms + buffer)
            }
        }, true); // Use capture phase to catch events early
        
        // Also listen for Preline overlay close events if available
        if (window.HSOverlay) {
            sidebar.addEventListener('close.hs.overlay', function() {
                setTimeout(removeBackdropOverlays, 150);
            });
        }
    });
    </script>

    <div id="hs-scale-animation-modal-started" class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none" role="dialog" tabindex="-1" aria-labelledby="hs-scale-animation-modal-label-started">
    <div class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
        <div class="w-full flex flex-col bg-white border border-gray-200 shadow-2xs rounded-xl pointer-events-auto">
            <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200">
                <h3 id="hs-scale-animation-modal-label-started" class="flex gap-2 font-bold text-gray-800">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                        <path d="M4.5 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM14.25 8.625a3.375 3.375 0 116.75 0 3.375 3.375 0 01-6.75 0zM1.5 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM17.25 19.128l-.001.144a2.25 2.25 0 01-.233.96 10.088 10.088 0 005.06-1.01.75.75 0 00.42-.643 4.875 4.875 0 00-6.957-4.611 8.586 8.586 0 011.71 5.157v.003z" />
                    </svg>
                    User onboarding
                </h3>
                <button type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-full border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 focus:outline-hidden focus:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none" aria-label="Close" data-hs-overlay="#hs-scale-animation-modal-started">
                    <span class="sr-only">Close</span>
                    <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-4 overflow-y-auto">
                <p class="text-sm text-gray-500">
                    Please follow the instruction on the links below to get started.
                </p>
                <p class="text-sm text-gray-800 mt-4">
                    <a href="https://docs.google.com/document/d/1-K14FxXrvpC6hPXrIW-wRHgCa3uqO8_Ppq3_Qh_EjQE/edit" target="_blank" class="font-medium text-[#0077b5] hover:text-[#005885] hover:underline"> 
                        Get linkedIn profile ID
                    </a>
                    <span class="px-4">|</span>
                    <a href="https://vimeo.com/922196997" class="fancy-box font-medium text-[#0077b5] hover:text-[#005885] hover:underline"> 
                        Watch onboarding video
                    </a>
                </p>
            </div>
            <div class="flex justify-end items-center gap-x-2 py-3 px-4 border-t border-gray-200">
                <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-2xs hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none" data-hs-overlay="#hs-scale-animation-modal-started">
                Close
                </button>
                <a href="https://chromewebstore.google.com/detail/ndfihhnfngenanpnlopcongadglbpmpe" target="_blank" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent text-white transition-all disabled:opacity-50 disabled:pointer-events-none" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">
                    Download Chrome Extension
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>