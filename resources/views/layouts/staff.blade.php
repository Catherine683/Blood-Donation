<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-neutral-100 text-neutral-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#B3262E">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    
    <title>{{ filled($title ?? null) ? $title . ' — Staff Portal — DonorLink UG' : 'Staff Portal — DonorLink UG' }}</title>
    <style>[x-cloak] { display: none !important; }</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased bg-neutral-100 text-neutral-900 flex overflow-hidden" x-data="{ sidebarOpen: false, collapsed: false }">
    {{-- Skip to content link for accessibility --}}
    <a href="#staff-main" class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2.5 focus:bg-primary-600 focus:text-white focus:rounded-lg focus:shadow-lg focus:font-medium">
        Skip to main content
    </a>

    {{-- Mobile Drawer Backdrop --}}
    <div
        x-show="sidebarOpen"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        class="fixed inset-0 z-40 bg-neutral-900/60 backdrop-blur-xs lg:hidden"
        x-on:click="sidebarOpen = false"
        aria-hidden="true"
    ></div>

    {{-- Sidebar (Desktop Static/Fixed & Mobile Drawer) --}}
    <aside
        :class="{
            'translate-x-0': sidebarOpen,
            '-translate-x-full lg:translate-x-0': !sidebarOpen,
            'w-64': !collapsed,
            'lg:w-20 w-64': collapsed
        }"
        class="fixed inset-y-0 left-0 z-40 flex flex-col h-full bg-white border-r border-neutral-200/90 shadow-xs transition-all duration-200 lg:static lg:z-30 shrink-0"
        aria-label="Staff navigation sidebar"
    >
        {{-- Sidebar Brand Header --}}
        <div class="h-16 shrink-0 px-4 flex items-center justify-between border-b border-neutral-200/90 bg-white">
            <a href="{{ route('staff.dashboard') }}" class="flex items-center gap-2 overflow-hidden focus-visible:rounded-lg">
                <x-logo size="sm" ::icon-only="collapsed" />
            </a>
            {{-- Mobile Close Button --}}
            <button
                type="button"
                x-on:click="sidebarOpen = false"
                class="lg:hidden p-1.5 rounded-lg text-neutral-500 hover:text-neutral-800 hover:bg-neutral-100 focus-visible:outline-primary-600 cursor-pointer min-h-[44px] min-w-[44px] flex items-center justify-center"
                aria-label="Close navigation"
            >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Navigation Menu (scrolls independently if sidebar height overflows) --}}
        <nav class="flex-1 px-3 py-4 space-y-1.5 overflow-y-auto" aria-label="Staff sections">
            {{-- Dashboard --}}
            <a
                href="{{ route('staff.dashboard') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('staff.dashboard') ? 'bg-primary-50 text-primary-700 font-semibold' : 'text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900' }}"
                :title="collapsed ? 'Dashboard' : null"
            >
                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('staff.dashboard') ? 'text-primary-600' : 'text-neutral-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span x-show="!collapsed" class="truncate">Dashboard</span>
            </a>

            {{-- Drives --}}
            <a
                href="{{ route('staff.drives') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('staff.drives') ? 'bg-primary-50 text-primary-700 font-semibold' : 'text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900' }}"
                :title="collapsed ? 'Drives' : null"
            >
                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('staff.drives') ? 'text-primary-600' : 'text-neutral-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <span x-show="!collapsed" class="truncate">Drives</span>
            </a>

            {{-- Donors --}}
            <a
                href="{{ route('staff.donors') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('staff.donors') ? 'bg-primary-50 text-primary-700 font-semibold' : 'text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900' }}"
                :title="collapsed ? 'Donors' : null"
            >
                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('staff.donors') ? 'text-primary-600' : 'text-neutral-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span x-show="!collapsed" class="truncate">Donors</span>
            </a>

            {{-- Admin Only Section --}}
            @if (auth()->user()?->isAdmin())
                <div class="pt-4 mt-4 border-t border-neutral-200/80">
                    <p x-show="!collapsed" class="px-3 text-xs font-semibold uppercase tracking-wider text-neutral-400 mb-2">
                        Administration
                    </p>

                    {{-- Staff Management --}}
                    <a
                        href="{{ route('admin.staff') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('admin.staff') ? 'bg-primary-50 text-primary-700 font-semibold' : 'text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900' }}"
                        :title="collapsed ? 'Staff' : null"
                    >
                        <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.staff') ? 'text-primary-600' : 'text-neutral-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span x-show="!collapsed" class="truncate">Staff Accounts</span>
                    </a>

                    {{-- Audit Log --}}
                    <a
                        href="{{ route('admin.audit-log') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('admin.audit-log') ? 'bg-primary-50 text-primary-700 font-semibold' : 'text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900' }}"
                        :title="collapsed ? 'Audit Log' : null"
                    >
                        <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.audit-log') ? 'text-primary-600' : 'text-neutral-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span x-show="!collapsed" class="truncate">Audit Log</span>
                    </a>

                    {{-- Email Notifications Log --}}
                    <a
                        href="{{ route('admin.notifications.index') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('admin.notifications.*') ? 'bg-primary-50 text-primary-700 font-semibold' : 'text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900' }}"
                        :title="collapsed ? 'Email Notifications' : null"
                    >
                        <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.notifications.*') ? 'text-primary-600' : 'text-neutral-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span x-show="!collapsed" class="truncate">Email Log</span>
                    </a>

                    {{-- System Telemetry --}}
                    <a
                        href="{{ route('admin.system') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('admin.system*') ? 'bg-primary-50 text-primary-700 font-semibold' : 'text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900' }}"
                        :title="collapsed ? 'System Telemetry' : null"
                    >
                        <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.system*') ? 'text-primary-600' : 'text-neutral-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                        </svg>
                        <span x-show="!collapsed" class="truncate">System Status</span>
                    </a>
                </div>
            @endif
        </nav>

        {{-- Desktop Sidebar Collapse Toggle --}}
        <div class="hidden lg:block px-3 py-2 border-t border-neutral-200/80 shrink-0">
            <button
                type="button"
                x-on:click="collapsed = !collapsed"
                class="w-full flex items-center justify-center p-2 rounded-lg text-neutral-500 hover:bg-neutral-100 hover:text-neutral-800 transition-colors focus-visible:outline-primary-600 cursor-pointer min-h-[44px]"
                :title="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
            >
                <svg x-show="!collapsed" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                </svg>
                <svg x-show="collapsed" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                </svg>
            </button>
        </div>

        {{-- User Footer Info --}}
        <div class="p-3 border-t border-neutral-200/80 bg-neutral-50/70 shrink-0">
            <div class="flex items-center gap-3 overflow-hidden">
                <span class="w-9 h-9 rounded-full bg-gradient-to-br from-primary-600 to-primary-700 text-white font-semibold text-xs flex items-center justify-center shrink-0 shadow-xs ring-1 ring-primary-200">
                    {{ auth()->user()?->initials() ?? 'S' }}
                </span>
                <div x-show="!collapsed" class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-neutral-900 truncate">{{ auth()->user()?->name ?? 'Staff User' }}</p>
                    <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-semibold uppercase tracking-wider {{ auth()->user()?->isAdmin() ? 'bg-amber-100 text-amber-800' : 'bg-primary-100 text-primary-800' }}">
                        {{ auth()->user()?->role?->label() ?? 'Staff' }}
                    </span>
                </div>
            </div>

            <form method="POST" action="{{ route('logout') }}" class="mt-2" x-show="!collapsed">
                @csrf
                <button type="submit" class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-medium text-danger-700 hover:bg-danger-50 transition-colors text-left cursor-pointer min-h-[44px]">
                    <svg class="w-4 h-4 text-danger-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Log Out
                </button>
            </form>
        </div>
    </aside>

    {{-- Main Content Area with Fixed Top Header --}}
    <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden">
        {{-- Unified Top Header Bar (Desktop & Mobile) --}}
        <header class="h-16 shrink-0 bg-white border-b border-neutral-200/90 px-4 sm:px-6 lg:px-8 flex items-center justify-between z-20 shadow-2xs">
            {{-- Left: Mobile Hamburger & Context / Breadcrumb --}}
            <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                <button
                    type="button"
                    x-on:click="sidebarOpen = true"
                    class="lg:hidden p-2 -ml-2 rounded-lg text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100 focus-visible:outline-primary-600 cursor-pointer min-h-[44px] min-w-[44px] flex items-center justify-center"
                    aria-label="Open staff navigation menu"
                >
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <div class="lg:hidden flex items-center">
                    <x-logo size="sm" />
                </div>

                {{-- Desktop Subtitle & Portal Indicator --}}
                <div class="hidden lg:flex items-center gap-2 text-sm">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-neutral-100 text-neutral-700 border border-neutral-200/80">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        UBTS Operations
                    </span>
                    <span class="text-neutral-400">/</span>
                    <span class="font-medium text-neutral-600 truncate max-w-xs xl:max-w-md">
                        {{ $title ?? 'Operations Management' }}
                    </span>
                </div>
            </div>

            {{-- Right: Quick Status & User Profile Menu --}}
            <div class="flex items-center gap-3">
                {{-- Quick Link to Public Drives Directory --}}
                <a
                    href="{{ route('drives.index') }}"
                    target="_blank"
                    class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100 transition-colors"
                    title="View public portal in new tab"
                >
                    <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                    <span>Public View</span>
                </a>

                {{-- User Profile Dropdown --}}
                <div x-data="{ userMenuOpen: false }" class="relative" x-on:keydown.escape.window="userMenuOpen = false">
                    <button
                        type="button"
                        x-on:click="userMenuOpen = !userMenuOpen"
                        x-on:click.outside="userMenuOpen = false"
                        class="flex items-center gap-2.5 p-1.5 rounded-xl hover:bg-neutral-100 transition-all focus-visible:outline-primary-600 cursor-pointer min-h-[44px]"
                        aria-expanded="false"
                        :aria-expanded="userMenuOpen.toString()"
                        aria-haspopup="true"
                        aria-label="User profile options menu"
                    >
                        <div class="relative">
                            <span class="w-9 h-9 rounded-full bg-gradient-to-br from-primary-600 to-primary-800 text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-xs ring-2 ring-primary-100">
                                {{ auth()->user()?->initials() ?? 'S' }}
                            </span>
                            <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                        </div>

                        <div class="hidden sm:flex flex-col text-left">
                            <span class="text-sm font-semibold text-neutral-900 leading-tight truncate max-w-[150px]">
                                {{ auth()->user()?->name ?? 'Staff User' }}
                            </span>
                            <span class="text-[11px] font-medium leading-tight {{ auth()->user()?->isAdmin() ? 'text-amber-700' : 'text-primary-700' }}">
                                {{ auth()->user()?->role?->label() ?? 'Staff' }}
                            </span>
                        </div>

                        <svg class="w-4 h-4 text-neutral-400 transition-transform duration-200" :class="{ 'rotate-180': userMenuOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    {{-- User Dropdown Card --}}
                    <div
                        x-show="userMenuOpen"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        x-cloak
                        class="absolute right-0 mt-2 w-64 rounded-xl bg-white shadow-xl ring-1 ring-black/5 divide-y divide-neutral-100 py-1.5 z-50 text-neutral-800"
                        role="menu"
                    >
                        {{-- User Header Details --}}
                        <div class="px-4 py-3">
                            <p class="text-xs text-neutral-500">Signed in as</p>
                            <p class="text-sm font-semibold text-neutral-900 truncate">{{ auth()->user()?->name }}</p>
                            <p class="text-xs text-neutral-500 truncate mt-0.5">{{ auth()->user()?->email }}</p>
                            <div class="mt-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ auth()->user()?->isAdmin() ? 'bg-amber-100 text-amber-800' : 'bg-primary-100 text-primary-800' }}">
                                    {{ auth()->user()?->role?->label() ?? 'Staff' }}
                                </span>
                            </div>
                        </div>

                        {{-- Nav Links --}}
                        <div class="py-1">
                            <a
                                href="{{ route('profile.edit') }}"
                                class="flex items-center gap-2.5 px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-50 hover:text-neutral-900 transition-colors"
                                role="menuitem"
                            >
                                <svg class="w-4 h-4 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span>Profile Settings</span>
                            </a>

                            <a
                                href="{{ route('security.edit') }}"
                                class="flex items-center gap-2.5 px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-50 hover:text-neutral-900 transition-colors"
                                role="menuitem"
                            >
                                <svg class="w-4 h-4 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                <span>Security & Password</span>
                            </a>

                            @if (auth()->user()?->isAdmin())
                                <a
                                    href="{{ route('admin.system') }}"
                                    class="flex items-center gap-2.5 px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-50 hover:text-neutral-900 transition-colors"
                                    role="menuitem"
                                >
                                    <svg class="w-4 h-4 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                                    </svg>
                                    <span>System Telemetry</span>
                                </a>
                            @endif
                        </div>

                        {{-- Sign Out Action --}}
                        <div class="py-1">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button
                                    type="submit"
                                    class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-danger-700 hover:bg-danger-50 hover:text-danger-900 transition-colors cursor-pointer text-left"
                                    role="menuitem"
                                >
                                    <svg class="w-4 h-4 text-danger-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    <span>Sign Out</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        {{-- Scrollable Page Content --}}
        <main id="staff-main" class="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto bg-neutral-100/70">
            {{ $slot }}
        </main>
    </div>
</body>
</html>
