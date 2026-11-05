<div class="flex flex-col h-full bg-white/80 backdrop-blur-xl relative overflow-hidden border-r border-gray-100">
    <!-- Decorative Background Elements -->
    <div class="absolute inset-0 z-0 pointer-events-none overflow-hidden opacity-80">
        <!-- Blue Gradient Blob -->
        <div class="absolute -top-10 -left-10 w-48 h-48 rounded-full bg-[#0038a8] opacity-[0.08] blur-2xl"></div>
        <!-- Yellow Gradient Blob -->
        <div class="absolute top-[35%] -right-10 w-32 h-32 rounded-full bg-[#fcd116] opacity-[0.12] blur-xl"></div>
        <!-- Red Gradient Blob -->
        <div class="absolute bottom-10 -left-10 w-40 h-40 rounded-full bg-[#ce1126] opacity-[0.08] blur-2xl"></div>
    </div>

    <!-- Main Content Container -->
    <div class="relative z-10 flex flex-col h-full">

        <!-- Logo -->
        <div class="px-4 py-3 flex items-center gap-3 shrink-0 border-b border-gray-100" :class="sidebarCollapsed ? 'justify-center px-3' : ''">
            <a href="{{ route('dashboard') }}"
                class="flex items-center gap-3 min-w-0 group transition-all duration-300">
                <x-application-logo class="w-20 h-12 shrink-0 object-contain drop-shadow-sm transition-all duration-300" />
                <span
                    x-show="!sidebarCollapsed"
                    x-transition
                    class="text-[9px] leading-tight font-black tracking-tight bg-gradient-to-r from-[#0038a8] to-[#ce1126] bg-clip-text text-transparent">
                    Department of <br> Migrant Workers
                </span>
            </a>

            <button
                type="button"
                class="ml-auto h-9 w-9 shrink-0 items-center justify-center rounded-lg text-gray-500 hover:text-[#0038a8] hover:bg-blue-50 transition"
                :class="sidebarCollapsed ? 'hidden' : 'inline-flex'"
                @click="sidebarCollapsed = !sidebarCollapsed"
                title="Collapse sidebar"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16" />
                </svg>
            </button>

            <button
                type="button"
                class="h-9 w-9 shrink-0 items-center justify-center rounded-lg text-gray-500 hover:text-[#0038a8] hover:bg-blue-50 transition"
                :class="sidebarCollapsed ? 'inline-flex' : 'hidden'"
                @click="sidebarCollapsed = !sidebarCollapsed"
                title="Expand sidebar"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16" />
                </svg>
            </button>
        </div>

    <!-- Navigation -->
    @php($isApproved = auth()->user()?->is_approved)
    @php($sidebarRole = strtolower(auth()->user()->role ?? ''))
    @php($isCtoManagedDetail = request()->routeIs('my-cto.show') && in_array($sidebarRole, ['admin', 'hrstaff', 'hr staff', 'chief', 'regionaldirector']))
    @php($isTarManagementView = request()->routeIs('travel-orders.ar.*') && in_array($sidebarRole, ['admin', 'hrstaff', 'hr staff', 'chief', 'regionaldirector', 'regional director', 'recordofficer', 'record officer'], true))
    <nav class="flex-1 px-4 space-y-1 overflow-y-auto mt-2">
        <div class="pb-3 mb-3 border-b border-gray-50">
            <h3 x-show="!sidebarCollapsed" x-transition class="px-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1.5">
                {{ __('Main Menu') }}
            </h3>

            <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                        </path>
                    </svg>
                </x-slot>
                {{ __('Dashboard') }}
            </x-sidebar-link>

            @if($isApproved)
                @php($canManageAnnouncements = in_array(auth()->user()->role, ['admin', 'hrstaff', 'chief', 'regionaldirector']))
                <x-sidebar-link :href="$canManageAnnouncements ? route('announcements.index') : route('announcements.view')" :active="request()->routeIs(['announcements.*'])">
                    <x-slot name="icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z">
                            </path>
                        </svg>
                    </x-slot>
                    {{ __('Announcements') }}
                </x-sidebar-link>
            @endif

            @if($isApproved)
                @if(in_array(strtolower($sidebarRole), ['employee', 'chief', 'regionaldirector', 'regional director', 'recordofficer', 'record officer'], true))
                    <x-sidebar-dropdown label="{{ __('My Account') }}" :active="request()->routeIs(['pds.*', 'salns.*', 'ildp.*', 'leaves.*', 'my-dtr.*', 'locator-slips.*']) || (request()->routeIs('travel-orders.*') && ! $isTarManagementView) || (request()->routeIs('my-cto.*') && ! $isCtoManagedDetail)">
                        <x-slot name="icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </x-slot>

                        <x-sidebar-link :href="route('pds.index')" :active="request()->routeIs('pds.*')" class="text-xs">
                            {{ __('PDS') }}
                        </x-sidebar-link>

                        <x-sidebar-link :href="route('salns.index')" :active="request()->routeIs('salns.*')" class="text-xs">
                            {{ __('SALN') }}
                        </x-sidebar-link>

                        <x-sidebar-link :href="route('locator-slips.index')" :active="request()->routeIs('locator-slips.*')" class="text-xs">
                            {{ __('Locator Slip') }}
                        </x-sidebar-link>

                        <x-sidebar-link :href="route('leaves.index')" :active="request()->routeIs('leaves.*')" class="text-xs">
                            {{ __('My Leave Application') }}
                        </x-sidebar-link>

                        <x-sidebar-link :href="route('my-dtr.index')" :active="request()->routeIs('my-dtr.*')" class="text-xs">
                            {{ __('Attendance / DTR') }}
                        </x-sidebar-link>

                        <x-sidebar-link :href="route('travel-orders.index')" :active="request()->routeIs('travel-orders.*') && ! $isTarManagementView" class="text-xs">
                            {{ __('Travel Authority') }}
                        </x-sidebar-link>

                        <x-sidebar-link :href="route('my-cto.index')" :active="request()->routeIs('my-cto.*') && ! $isCtoManagedDetail" class="text-xs">
                            {{ __('Compensatory Time-Off') }}
                        </x-sidebar-link>
                    </x-sidebar-dropdown>
                @endif
            @endif
        </div>

        @if($isApproved && in_array(strtolower(auth()->user()->role ?? ''), ['admin', 'hrstaff', 'hr staff', 'chief', 'regionaldirector', 'regional director', 'recordofficer', 'record officer'], true))
            <div class="pb-3">
                <h3 x-show="!sidebarCollapsed" x-transition class="px-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1.5">
                    {{ __('Administration') }}
                </h3>

                @if(! in_array(strtolower(auth()->user()->role ?? ''), ['chief', 'regionaldirector', 'regional director', 'recordofficer', 'record officer'], true))
                    <x-sidebar-link :href="route('employees.index')" :active="request()->routeIs('employees.*')">
                        <x-slot name="icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                                </path>
                            </svg>
                        </x-slot>
                        {{ __('Manage Employee') }}
                    </x-sidebar-link>
                @endif

                @if(in_array(strtolower(auth()->user()->role ?? ''), ['admin', 'hrstaff', 'hr staff', 'chief', 'regionaldirector', 'regional director'], true))
                    <x-sidebar-link :href="route('leave-applications.index')" :active="request()->routeIs(['leave-applications.*'])">
                        <x-slot name="icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                </path>
                            </svg>
                        </x-slot>
                        {{ __('Manage Leave') }}
                    </x-sidebar-link>
                @endif

                @if(in_array(strtolower(auth()->user()->role ?? ''), ['admin', 'hrstaff', 'hr staff', 'regionaldirector', 'regional director', 'recordofficer', 'record officer'], true))
                    <x-sidebar-link :href="route('hr.travel-orders.index')" :active="request()->routeIs('hr.travel-orders.*') || request()->routeIs('travel-orders.ar.*')">
                        <x-slot name="icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </x-slot>
                        {{ __('Manage Travel Authority') }}
                    </x-sidebar-link>
                @endif

                @if(strtolower(auth()->user()->role ?? '') === 'chief')
                    <x-sidebar-link :href="route('hr.travel-orders.index', ['tab' => 'reports'])" :active="request()->routeIs('hr.travel-orders.*') || request()->routeIs('travel-orders.ar.*')">
                        <x-slot name="icon">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z" />
                            </svg>
                        </x-slot>
                        {{ __('Manage TAR') }}
                    </x-sidebar-link>
                @endif

                @if(in_array(strtolower(auth()->user()->role ?? ''), ['admin', 'hrstaff', 'hr staff', 'chief', 'regionaldirector', 'regional director'], true))
                    <x-sidebar-link :href="route('hr.cto.index')" :active="request()->routeIs('hr.cto.*') || request()->routeIs('cto.update-status') || $isCtoManagedDetail">
                        <x-slot name="icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </x-slot>
                        {{ __('Manage CTO') }}
                    </x-sidebar-link>
                @endif

                @if(in_array(strtolower(auth()->user()->role ?? ''), ['admin', 'hrstaff', 'hr staff', 'chief'], true))
                    <x-sidebar-link :href="route('hr.locator-slips.index')" :active="request()->routeIs('hr.locator-slips.*')">
                        <x-slot name="icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6.253v11.494m-9-5.747h18"></path>
                            </svg>
                        </x-slot>
                        {{ __('Manage Locator Slip') }}
                    </x-sidebar-link>
                @endif

                @if(in_array(strtolower(auth()->user()->role ?? ''), ['admin', 'hrstaff', 'hr staff'], true))
                    <x-sidebar-link :href="route('dtr.index')" :active="request()->routeIs('dtr.*')">
                        <x-slot name="icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </x-slot>
                        {{ __('Attendance') }}
                    </x-sidebar-link>
                @endif

                @if(in_array(auth()->user()->role, ['admin', 'hrstaff']))
                    <x-sidebar-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                        <x-slot name="icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 17v-6m4 6V7m4 10v-4M5 19h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                </path>
                            </svg>
                        </x-slot>
                        {{ __('Reports') }}
                    </x-sidebar-link>
                @endif

                @if(in_array(strtolower(auth()->user()->role ?? ''), ['admin', 'hrstaff', 'hr staff'], true))
                    <x-sidebar-link :href="route('audit-logs.login')" :active="request()->routeIs('audit-logs.*')">
                        <x-slot name="icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </x-slot>
                        {{ __('Audit Log') }}
                    </x-sidebar-link>
                @endif

                
            </div>
        @endif

        <div class="pb-3">
            <h3 x-show="!sidebarCollapsed" x-transition class="px-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1.5">
                {{ __('Settings') }}
            </h3>

            @if($isApproved)
                <x-sidebar-link :href="route('profile.edit')" :active="request()->routeIs('profile.edit')">
                    <x-slot name="icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </x-slot>
                    {{ __('Profile') }}
                </x-sidebar-link>
            @endif

            @if($isApproved && in_array(auth()->user()->role, ['admin', 'hrstaff', 'regionaldirector']))
                <x-sidebar-link :href="route('utilities.index')" :active="request()->routeIs('utilities.*') || request()->routeIs('leave-types.*')">
                    <x-slot name="icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </x-slot>
                    {{ __('Utilities') }}
                </x-sidebar-link>
            @endif

        </div>
    </nav>

    <!-- Fixed Logout Button at the bottom -->
    <div class="p-4 border-t border-gray-100 bg-white/50 shrink-0">
        <form method="POST" action="{{ route('logout') }}" class="group">
            @csrf
            <button type="submit"
                class="w-full flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg text-red-600 hover:bg-red-50 hover:text-red-700 transition duration-150 ease-in-out"
                :class="sidebarCollapsed ? 'justify-center px-2' : ''">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                    </path>
                </svg>
                <span x-show="!sidebarCollapsed" x-transition>{{ __('Log Out') }}</span>
            </button>
        </form>
    </div>
    </div> <!-- /End relative z-10 container -->
</div>



