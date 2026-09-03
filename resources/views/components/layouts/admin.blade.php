<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ sidebar: false, dark: localStorage.theme === 'dark' }" :class="{ 'dark': dark }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Workspace' }} · TravelSync</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="admin-canvas min-h-screen text-slate-800 antialiased dark:text-slate-100">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:z-[100] focus:m-3 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">Skip to content</a>
    <div class="min-h-screen lg:grid lg:grid-cols-[260px_1fr]">
        <div x-show="sidebar" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/45 lg:hidden" @click="sidebar = false"></div>
        <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 flex w-[260px] flex-col border-r border-indigo-100 bg-white/95 shadow-[8px_0_30px_rgba(30,64,175,.04)] backdrop-blur transition-transform duration-200 dark:border-slate-800 dark:bg-slate-900/95 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0">
            <div class="flex h-16 items-center gap-3 border-b border-slate-100 px-5 dark:border-slate-800">
                <span class="grid size-9 place-items-center rounded-xl bg-gradient-to-br from-blue-500 via-indigo-600 to-violet-600 font-bold text-white shadow-lg shadow-indigo-500/25">T</span>
                <div><p class="font-bold tracking-tight">TravelSync</p><p class="text-xs text-slate-500">Operations workspace</p></div>
                <button class="ml-auto rounded-lg p-2 lg:hidden" @click="sidebar = false" aria-label="Close navigation">×</button>
            </div>
            <nav class="admin-scroll flex-1 space-y-6 overflow-y-auto px-3 py-5" aria-label="Main navigation">
                @foreach(\App\Support\AdminNavigation::for(auth()->user()) as $group)
                    <section x-data="{ open: true }">
                        <button type="button" @click="open = !open" class="flex w-full items-center justify-between px-3 text-[11px] font-bold uppercase tracking-[.14em] text-slate-400" :aria-expanded="open"><span>{{ $group['label'] }}</span><span aria-hidden="true" x-text="open ? '−' : '+'"></span></button>
                        <div x-show="open" class="mt-2 space-y-1">
                            @foreach($group['items'] as $item)
                                <a href="{{ route($item['route']) }}" @class(['nav-link', 'nav-link-active' => request()->routeIs($item['active'])]) @if(request()->routeIs($item['active'])) aria-current="page" @endif>
                                    <svg class="size-4 shrink-0" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 5.5h16v13H4zM8 9h8M8 13h5" stroke-linecap="round" stroke-linejoin="round"/></svg>{{ $item['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </nav>
            <div class="border-t border-slate-100 p-4 dark:border-slate-800">
                <div class="flex items-center gap-3 rounded-xl bg-slate-50 p-3 dark:bg-slate-800">
                    <span class="grid size-9 place-items-center rounded-full bg-slate-800 text-sm font-bold text-white">{{ Str::upper(Str::substr(auth()->user()->name, 0, 2)) }}</span>
                    <div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p><p class="truncate text-xs capitalize text-slate-500">{{ str_replace('_', ' ', auth()->user()->role) }}</p></div>
                </div>
            </div>
        </aside>
        <div class="min-w-0">
            <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-indigo-100 bg-white/80 px-4 shadow-sm shadow-blue-900/[.03] backdrop-blur-xl lg:px-7 dark:border-slate-800 dark:bg-slate-900/85">
                <button @click="sidebar = true" class="btn-icon lg:hidden" aria-label="Open navigation">☰</button>
                <form action="{{ route('admin.leads.index') }}" class="relative hidden w-full max-w-md sm:block">
                    <label for="global-search" class="sr-only">Search leads</label>
                    <input id="global-search" name="search" class="form-input pl-10" placeholder="Search leads, references, customers…">
                    <span class="pointer-events-none absolute left-3 top-2.5 text-slate-400">⌕</span>
                </form>
                <div class="ml-auto flex items-center gap-2">
                    <button class="btn-icon" @click="dark = !dark; localStorage.theme = dark ? 'dark' : 'light'" aria-label="Toggle dark mode">◐</button>
                    <a href="{{ route('admin.notifications') }}" class="btn-icon relative" aria-label="Notifications">♢@if(auth()->user()->unreadNotifications()->exists())<span class="absolute right-1 top-1 size-2 rounded-full bg-rose-500"></span>@endif</a>
                    <form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="btn-secondary">Sign out</button></form>
                </div>
            </header>
            <main id="main-content" class="p-4 lg:p-7">
                <div wire:loading.delay class="fixed inset-x-0 top-0 z-[100] h-1 animate-pulse bg-blue-600" role="status" aria-label="Loading"></div>
                <nav class="mb-4 flex items-center gap-2 text-xs text-slate-500" aria-label="Breadcrumb"><a href="{{ route('admin.dashboard') }}" class="hover:text-blue-600">Home</a>@unless(request()->routeIs('admin.dashboard'))<span>/</span><span class="font-medium text-slate-700 dark:text-slate-300">{{ $title ?? Str::headline(request()->route()?->getName()) }}</span>@endunless</nav>
                @if(session('success'))<div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
                {{ $slot }}
            </main>
        </div>
    </div>
    @livewireScripts
</body>
</html>
