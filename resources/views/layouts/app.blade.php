<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'DMW-HRIS') }}</title>

        <!-- Favicon -->
        <link rel="icon" href="data:,">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:300,400,500,600,700,800&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased text-gray-900 bg-gray-50/50" x-data="{ sidebarOpen: false, sidebarCollapsed: false }">
        <div class="flex h-screen overflow-hidden">
            <!-- Off-canvas menu for mobile -->
            <div x-show="sidebarOpen" 
                 class="fixed inset-0 z-50 flex lg:hidden" 
                 x-description="Off-canvas menu for mobile, show/hide based on off-canvas menu state." 
                 role="dialog" 
                 aria-modal="true"
                 style="display: none;">
                
                <div x-show="sidebarOpen" 
                     x-transition:enter="transition-opacity ease-linear duration-300" 
                     x-transition:enter-start="opacity-0" 
                     x-transition:enter-end="opacity-100" 
                     x-transition:leave="transition-opacity ease-linear duration-300" 
                     x-transition:leave-start="opacity-100" 
                     x-transition:leave-end="opacity-0" 
                     class="fixed inset-0 bg-gray-600/80 backdrop-blur-sm" 
                     aria-hidden="true"
                     @click="sidebarOpen = false"></div>

                <div x-show="sidebarOpen" 
                     x-transition:enter="transition ease-in-out duration-300 transform" 
                     x-transition:enter-start="-translate-x-full" 
                     x-transition:enter-end="translate-x-0" 
                     x-transition:leave="transition ease-in-out duration-300 transform" 
                     x-transition:leave-start="translate-x-0" 
                     x-transition:leave-end="-translate-x-full" 
                     class="relative flex w-full max-w-xs flex-col flex-1 bg-white focus:outline-none transition-all duration-300 shadow-2xl">
                    
                    <div class="absolute top-0 right-0 -mr-12 pt-2">
                        <button type="button" 
                                class="ml-1 flex h-10 w-10 items-center justify-center rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white" 
                                @click="sidebarOpen = false">
                            <span class="sr-only">Close sidebar</span>
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="h-full flex flex-col">
                        @include('layouts.sidebar')
                    </div>
                </div>

                <div class="w-14 shrink-0" aria-hidden="true">
                    <!-- Dummy element to force sidebar to that offset from left -->
                </div>
            </div>

            <!-- Collapsible sidebar for desktop -->
            <aside
                class="hidden lg:flex lg:flex-col lg:shrink-0 h-full transition-all duration-300"
                :class="sidebarCollapsed ? 'lg:w-20' : 'lg:w-72'"
            >
                @include('layouts.sidebar')
            </aside>

            <!-- Main content area -->
            <div class="flex flex-col flex-1 w-0 overflow-hidden bg-gray-50/50">
                <!-- Mobile top bar -->
                <header class="lg:hidden flex items-center justify-between h-16 px-4 shrink-0 bg-white border-b border-gray-100 shadow-sm z-40">
                    <button type="button" 
                            class="p-2 -ml-0.5 text-gray-500 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500" 
                            @click="sidebarOpen = true">
                        <span class="sr-only">Open sidebar</span>
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <div class="flex-1 flex justify-center lg:hidden">
                        <x-application-logo class="w-10 h-10 fill-current text-blue-600" />
                    </div>
                    <div class="flex items-center gap-2">
                        <livewire:notifications-dropdown wire:key="mobile-notifications" />
                    </div>
                </header>

                <header class="hidden lg:flex items-center justify-between h-16 px-8 shrink-0 bg-white/80 backdrop-blur-md border-b border-gray-100/50 shadow-sm z-30 sticky top-0 transition-all duration-300">
                    <div class="flex-1">
                        <h2 class="text-lg font-bold text-gray-800 tracking-tight">
                            {{ $title ?? 'Dashboard' }}
                        </h2>
                    </div>
                        <div class="flex items-center gap-3">
                        <livewire:notifications-dropdown wire:key="desktop-notifications" />
                        
                        <div class="h-6 w-px bg-gray-100 mx-1"></div>

                        <a href="{{ auth()->user()?->employee ? route('personal-information.show') : route('profile.edit') }}"
                           class="flex items-center gap-3 pl-2 rounded-xl hover:bg-gray-50 transition-colors duration-200 {{ request()->routeIs(['personal-information.*', 'profile.edit']) ? 'ring-2 ring-blue-900 bg-blue-50/50' : '' }}">
                            @php
                                $roleLabels = [
                                    'employee' => 'Employee',
                                    'hrstaff' => 'HR Admin',
                                    'recordofficer' => 'Record Officer',
                                    'chief' => 'Chief',
                                    'regionaldirector' => 'Regional Director',
                                    'admin' => 'Admin',
                                ];
                                $displayRole = $roleLabels[strtolower((string) (Auth::user()->role ?? 'employee'))] ?? 'Employee';
                                $rawPosition = Auth::user()?->employee?->position;
                                $positionKey = strtolower(str_replace(' ', '', (string) $rawPosition));
                                $displayPosition = $roleLabels[$positionKey] ?? ($rawPosition ?: $displayRole);
                            @endphp
                            <div class="text-right hidden xl:block">
                                <p class="text-sm font-bold text-gray-900 leading-none mb-0.5">{{ Auth::user()->display_name }}</p>
                                <p class="text-[10px] font-bold text-blue-500 tracking-tighter">{{ $displayPosition }}</p>
                            </div>
                            <x-profile-avatar :user="auth()->user()" size="md" class="ring-2 ring-white hover:ring-blue-100 transition-all duration-300" />
                        </a>
                    </div>
                </header>

                @php
                    $applicationListRoutes = [
                        'locator-slips.index',
                        'leaves.index',
                        'travel-orders.index',
                        'my-cto.index',
                        'leave-applications.index',
                        'leave-applications.all',
                        'hr.travel-orders.index',
                        'hr.locator-slips.index',
                        'hr.locator-slips.all',
                        'hr.locator-slips.pending',
                        'hr.cto.index',
                    ];
                    $isDashboardPage = request()->routeIs('dashboard');
                    $isFullWidthPage = $isDashboardPage || request()->routeIs($applicationListRoutes);
                    $flatContentRoutes = [...$applicationListRoutes, 'announcements.show'];
                    $isFlatContentPage = request()->routeIs($flatContentRoutes);
                    $contentPanelClass = $isFlatContentPage
                        ? 'overflow-visible bg-transparent shadow-none border-0'
                        : ($isDashboardPage
                            ? 'overflow-hidden bg-white rounded-xl shadow-sm border border-gray-100/10'
                            : 'overflow-hidden bg-white rounded-2xl shadow-sm border border-gray-100/10');
                @endphp
                <main class="flex-1 relative overflow-x-hidden focus:outline-none {{ request()->routeIs('dashboard') ? 'overflow-hidden py-3' : 'overflow-y-auto py-6' }}">
                    <div class="mx-auto w-full min-w-0 {{ $isFullWidthPage ? 'max-w-none px-1 sm:px-2 md:px-3' : 'max-w-7xl px-4 sm:px-6 md:px-8' }}">
                        <!-- Page Heading -->
                        @isset($header)
                            <div class="{{ request()->routeIs('dashboard') ? 'mb-3' : 'mb-8' }}">
                                {{ $header }}
                            </div>
                        @endisset

                        <!-- Page Content -->
                        <div class="w-full max-w-full min-w-0 transition-all duration-300 {{ $contentPanelClass }}">
                             {{ $slot }}
                        </div>
                    </div>
                </main>
            </div>
        </div>
        @livewireScripts
        <script>
            (() => {
                const activeClasses = ['bg-sky-50'];

                const highlightApprovalRow = (id) => {
                    if (!id) return;

                    document.querySelectorAll('[data-approval-row]').forEach((row) => {
                        row.classList.remove(...activeClasses);
                    });

                    const row = document.querySelector(`[data-approval-row="${CSS.escape(String(id))}"]`);
                    if (row) row.classList.add(...activeClasses);
                };

                ['leave-selected', 'travel-selected', 'cto-selected'].forEach((eventName) => {
                    window.addEventListener(eventName, (event) => highlightApprovalRow(event.detail?.id));
                });
            })();
        </script>
    </body>
</html>
