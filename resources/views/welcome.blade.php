<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="TravelSync is Ceysaid Holidays' connected workspace for sales, operations, ticketing, visas and finance.">
    <title>TravelSync · Ceysaid Holidays</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .hero-grid { background-image: linear-gradient(rgba(255,255,255,.055) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.055) 1px, transparent 1px); background-size: 52px 52px; }
        .orbit { animation: float 7s ease-in-out infinite; }
        .orbit-delayed { animation: float 7s ease-in-out 1.8s infinite; }
        @keyframes float { 0%,100% { transform: translateY(0) } 50% { transform: translateY(-10px) } }
        @media (prefers-reduced-motion: reduce) { .orbit,.orbit-delayed { animation: none } }
    </style>
</head>
<body class="bg-[#f7f9fc] text-slate-900 selection:bg-cyan-200 selection:text-slate-950">
    <header class="fixed inset-x-0 top-0 z-50 border-b border-white/10 bg-[#07142f]/90 text-white backdrop-blur-xl">
        <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-5 lg:px-8">
            <a href="#top" class="flex items-center gap-3" aria-label="TravelSync home">
                <span class="grid size-11 place-items-center rounded-2xl bg-white shadow-lg shadow-cyan-500/10"><img src="{{ asset('images/ceysaid-logo.png') }}" alt="Ceysaid" class="w-9"></span>
                <span><strong class="block text-lg tracking-tight">TravelSync</strong><small class="block text-[10px] font-semibold uppercase tracking-[.2em] text-cyan-300">by Ceysaid Holidays</small></span>
            </a>
            <nav class="hidden items-center gap-8 text-sm font-medium text-slate-300 md:flex" aria-label="Primary navigation">
                <a href="#workflow" class="transition hover:text-white">How it works</a>
                <a href="#capabilities" class="transition hover:text-white">Capabilities</a>
                <a href="#teams" class="transition hover:text-white">For teams</a>
            </nav>
            <a href="{{ auth()->check() ? route('admin.dashboard') : route('admin.login') }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-[#102454] shadow-lg shadow-black/10 transition hover:-translate-y-0.5 hover:bg-cyan-50">
                {{ auth()->check() ? 'Open workspace' : 'Staff login' }}
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
        </div>
    </header>

    <main id="top">
        <section class="hero-grid relative overflow-hidden bg-[#07142f] pb-24 pt-36 text-white lg:pb-32 lg:pt-44">
            <div class="absolute -left-40 top-16 size-[34rem] rounded-full bg-blue-600/20 blur-3xl"></div>
            <div class="absolute -right-24 bottom-0 size-[30rem] rounded-full bg-cyan-400/15 blur-3xl"></div>
            <div class="relative mx-auto grid max-w-7xl items-center gap-16 px-5 lg:grid-cols-[1.02fr_.98fr] lg:px-8">
                <div>
                    <div class="mb-7 inline-flex items-center gap-2 rounded-full border border-cyan-300/20 bg-cyan-300/10 px-3 py-1.5 text-xs font-bold uppercase tracking-[.16em] text-cyan-200"><span class="size-2 rounded-full bg-cyan-300 shadow-[0_0_14px_#67e8f9]"></span>Built for modern travel operations</div>
                    <h1 class="max-w-3xl text-5xl font-black leading-[1.03] tracking-[-.045em] sm:text-6xl lg:text-7xl">Every journey.<br><span class="bg-gradient-to-r from-cyan-300 via-sky-300 to-blue-400 bg-clip-text text-transparent">One connected workspace.</span></h1>
                    <p class="mt-7 max-w-2xl text-lg leading-8 text-slate-300">TravelSync brings leads, sales, visas, air tickets, tours and finance into one clear operational flow—so every team knows what happens next.</p>
                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ auth()->check() ? route('admin.dashboard') : route('admin.login') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-cyan-400 to-blue-500 px-6 py-3.5 font-bold text-slate-950 shadow-xl shadow-cyan-500/20 transition hover:-translate-y-1 hover:shadow-cyan-500/30">Enter TravelSync <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14m-5-5 5 5-5 5" stroke-linecap="round"/></svg></a>
                        <a href="#workflow" class="inline-flex items-center justify-center rounded-2xl border border-white/15 bg-white/5 px-6 py-3.5 font-semibold text-white transition hover:bg-white/10">Explore the workflow</a>
                    </div>
                    <div class="mt-10 flex flex-wrap gap-x-7 gap-y-3 text-sm text-slate-400"><span class="flex items-center gap-2"><b class="text-emerald-400">✓</b> Role-based access</span><span class="flex items-center gap-2"><b class="text-emerald-400">✓</b> Live operational status</span><span class="flex items-center gap-2"><b class="text-emerald-400">✓</b> One financial view</span></div>
                </div>

                <div class="relative mx-auto w-full max-w-xl">
                    <div class="orbit absolute -left-8 top-20 z-20 hidden rounded-2xl border border-white/15 bg-[#102454]/90 p-4 shadow-2xl backdrop-blur md:block"><div class="flex items-center gap-3"><span class="grid size-10 place-items-center rounded-xl bg-emerald-400/15 text-emerald-300">✓</span><div><p class="text-xs text-slate-400">Visa processing</p><strong class="text-sm">Documents complete</strong></div></div></div>
                    <div class="orbit-delayed absolute -right-8 bottom-20 z-20 hidden rounded-2xl border border-white/15 bg-[#102454]/90 p-4 shadow-2xl backdrop-blur md:block"><div class="flex items-center gap-3"><span class="grid size-10 place-items-center rounded-xl bg-amber-400/15 text-amber-300">✈</span><div><p class="text-xs text-slate-400">Air ticket</p><strong class="text-sm">Ready for issuing</strong></div></div></div>
                    <div class="relative overflow-hidden rounded-[2rem] border border-white/15 bg-white/95 p-3 shadow-[0_40px_100px_rgba(0,0,0,.38)]">
                        <div class="rounded-[1.5rem] bg-[#f5f7fb] p-5 text-slate-900">
                            <div class="flex items-center justify-between"><div><p class="text-xs font-bold uppercase tracking-[.15em] text-blue-600">Operations pulse</p><h2 class="mt-1 text-xl font-black">Today at a glance</h2></div><span class="grid size-10 place-items-center rounded-full bg-white text-sm font-bold shadow">GF</span></div>
                            <div class="mt-5 grid grid-cols-3 gap-3"><div class="rounded-2xl bg-[#102454] p-4 text-white"><p class="text-[11px] text-blue-200">Active leads</p><strong class="mt-2 block text-2xl">48</strong></div><div class="rounded-2xl bg-white p-4 shadow-sm"><p class="text-[11px] text-slate-500">Confirmed</p><strong class="mt-2 block text-2xl text-emerald-600">12</strong></div><div class="rounded-2xl bg-white p-4 shadow-sm"><p class="text-[11px] text-slate-500">Due today</p><strong class="mt-2 block text-2xl text-amber-600">06</strong></div></div>
                            <div class="mt-4 rounded-2xl bg-white p-4 shadow-sm"><div class="flex items-center justify-between"><strong class="text-sm">Lead journey</strong><span class="text-xs font-semibold text-blue-600">Live</span></div><div class="mt-5 flex items-center"><div class="text-center"><span class="mx-auto grid size-9 place-items-center rounded-xl bg-blue-600 text-xs font-bold text-white">01</span><small class="mt-2 block text-[10px] text-slate-500">Lead</small></div><span class="mb-5 h-0.5 flex-1 bg-blue-200"></span><div class="text-center"><span class="mx-auto grid size-9 place-items-center rounded-xl bg-indigo-600 text-xs font-bold text-white">02</span><small class="mt-2 block text-[10px] text-slate-500">Confirm</small></div><span class="mb-5 h-0.5 flex-1 bg-indigo-200"></span><div class="text-center"><span class="mx-auto grid size-9 place-items-center rounded-xl bg-cyan-500 text-xs font-bold text-white">03</span><small class="mt-2 block text-[10px] text-slate-500">Process</small></div><span class="mb-5 h-0.5 flex-1 bg-cyan-200"></span><div class="text-center"><span class="mx-auto grid size-9 place-items-center rounded-xl bg-emerald-500 text-white">✓</span><small class="mt-2 block text-[10px] text-slate-500">Done</small></div></div></div>
                            <div class="mt-4 grid gap-3 sm:grid-cols-2"><div class="rounded-2xl bg-gradient-to-br from-blue-50 to-cyan-50 p-4"><p class="text-xs font-semibold text-slate-500">Next receipt</p><p class="mt-2 font-black">LKR 325,000</p><small class="text-xs text-slate-500">Due tomorrow</small></div><div class="rounded-2xl bg-gradient-to-br from-amber-50 to-orange-50 p-4"><p class="text-xs font-semibold text-slate-500">Supplier payment</p><p class="mt-2 font-black">LKR 210,000</p><small class="text-xs text-slate-500">Due this week</small></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="workflow" class="py-24 lg:py-32">
            <div class="mx-auto max-w-7xl px-5 lg:px-8">
                <div class="max-w-3xl"><p class="text-xs font-black uppercase tracking-[.2em] text-blue-600">From first message to final payment</p><h2 class="mt-4 text-4xl font-black tracking-[-.035em] text-slate-950 sm:text-5xl">A workflow your whole team can follow.</h2><p class="mt-5 text-lg leading-8 text-slate-600">No scattered spreadsheets or invisible hand-offs. Every lead moves through a shared, accountable journey.</p></div>
                <div class="mt-14 grid gap-4 lg:grid-cols-5">
                    @foreach([
                        ['01','Capture','Leads arrive from every channel and are assigned with clear priority.','from-blue-600 to-blue-500'],
                        ['02','Convert','Sales manages follow-ups, quotes, confirmations and customer invoices.','from-indigo-600 to-violet-500'],
                        ['03','Fulfil','Operations coordinates visas, documents, tours and service delivery.','from-cyan-600 to-sky-500'],
                        ['04','Issue','Approved air-ticket requests move into a focused issuing queue.','from-amber-500 to-orange-500'],
                        ['05','Reconcile','Finance sees receipts, supplier bills, expenses and upcoming cash flow.','from-emerald-600 to-teal-500'],
                    ] as [$number,$title,$copy,$colour])
                        <article class="group relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl hover:shadow-blue-950/5"><span class="inline-flex rounded-xl bg-gradient-to-br {{ $colour }} px-3 py-2 text-xs font-black text-white">{{ $number }}</span><h3 class="mt-8 text-xl font-black">{{ $title }}</h3><p class="mt-3 text-sm leading-6 text-slate-600">{{ $copy }}</p><div class="absolute -bottom-10 -right-10 size-28 rounded-full bg-blue-50 transition group-hover:scale-125"></div></article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="capabilities" class="overflow-hidden bg-[#0b1936] py-24 text-white lg:py-32">
            <div class="mx-auto max-w-7xl px-5 lg:px-8">
                <div class="grid gap-14 lg:grid-cols-[.8fr_1.2fr] lg:items-start">
                    <div class="lg:sticky lg:top-32"><p class="text-xs font-black uppercase tracking-[.2em] text-cyan-300">Operational clarity</p><h2 class="mt-4 text-4xl font-black tracking-[-.035em] sm:text-5xl">The important work, visible at the right moment.</h2><p class="mt-6 text-lg leading-8 text-slate-300">Each team gets a focused workspace while managers keep the complete business picture.</p><a href="{{ auth()->check() ? route('admin.dashboard') : route('admin.login') }}" class="mt-8 inline-flex items-center gap-2 font-bold text-cyan-300 hover:text-white">Open the secure workspace <span>→</span></a></div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach([
                            ['Lead control','Priority highlighting, activity timelines, assignments and stage-based queues.','⌁'],
                            ['Visa workspace','Dedicated access, ownership and progress tracking for confirmed visa leads.','◇'],
                            ['Air ticket issuing','Accounts approval, pending queues, ticket details and lead completion.','✈'],
                            ['Tour operations','Group tours, fixed departures, capacity and shared supplier costs.','◎'],
                            ['Finance overview','Upcoming receivables, payables, cash movement and account-level expenses.','↗'],
                            ['Permissions & audit','Role-based visibility with accountable changes across every team.','✓'],
                        ] as [$title,$copy,$icon])
                            <article class="rounded-3xl border border-white/10 bg-white/[.055] p-6 backdrop-blur transition hover:border-cyan-300/30 hover:bg-white/[.08]"><span class="grid size-11 place-items-center rounded-2xl bg-cyan-300/10 text-xl text-cyan-300">{{ $icon }}</span><h3 class="mt-6 text-lg font-black">{{ $title }}</h3><p class="mt-3 text-sm leading-6 text-slate-300">{{ $copy }}</p></article>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section id="teams" class="py-24 lg:py-32">
            <div class="mx-auto max-w-7xl px-5 lg:px-8">
                <div class="text-center"><p class="text-xs font-black uppercase tracking-[.2em] text-blue-600">One system, role-aware views</p><h2 class="mt-4 text-4xl font-black tracking-[-.035em] sm:text-5xl">Built around how Ceysaid works.</h2></div>
                <div class="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    @foreach([
                        ['Sales','Own the pipeline, follow up faster and move confirmed customers forward.','bg-blue-600'],
                        ['Operations','Coordinate delivery, documents, visas and travel schedules without missed hand-offs.','bg-cyan-600'],
                        ['Accounts','Control approvals, collections, supplier payments, expenses and cash visibility.','bg-emerald-600'],
                        ['Management','See workload, performance, risk and financial position across the company.','bg-indigo-700'],
                    ] as [$title,$copy,$colour])
                        <article class="overflow-hidden rounded-3xl bg-white shadow-lg shadow-slate-900/5 ring-1 ring-slate-200"><div class="h-2 {{ $colour }}"></div><div class="p-7"><h3 class="text-xl font-black">{{ $title }}</h3><p class="mt-3 text-sm leading-6 text-slate-600">{{ $copy }}</p></div></article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="px-5 pb-24 lg:px-8 lg:pb-32">
            <div class="relative mx-auto max-w-7xl overflow-hidden rounded-[2.25rem] bg-gradient-to-br from-blue-700 via-indigo-700 to-[#07142f] px-6 py-14 text-center text-white shadow-2xl shadow-blue-950/20 sm:px-12 lg:py-20"><div class="absolute -left-20 -top-20 size-64 rounded-full border-[40px] border-white/5"></div><div class="absolute -bottom-32 -right-20 size-80 rounded-full bg-cyan-400/10"></div><div class="relative"><p class="text-xs font-black uppercase tracking-[.2em] text-cyan-200">Ready when you are</p><h2 class="mx-auto mt-4 max-w-3xl text-4xl font-black tracking-[-.035em] sm:text-5xl">Keep every booking, payment and hand-off moving.</h2><p class="mx-auto mt-5 max-w-2xl text-slate-200">Sign in to the secure Ceysaid workspace and continue where your team left off.</p><a href="{{ auth()->check() ? route('admin.dashboard') : route('admin.login') }}" class="mt-8 inline-flex items-center gap-2 rounded-2xl bg-white px-6 py-3.5 font-black text-blue-900 transition hover:-translate-y-1 hover:bg-cyan-50">{{ auth()->check() ? 'Go to dashboard' : 'Staff login' }} <span>→</span></a></div></div>
        </section>
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-5 px-5 py-8 text-center sm:flex-row sm:text-left lg:px-8"><div class="flex items-center gap-3"><img src="{{ asset('images/ceysaid-logo.png') }}" alt="Ceysaid Holidays" class="w-24"><span class="h-6 w-px bg-slate-200"></span><span class="text-sm font-bold text-slate-600">TravelSync</span></div><p class="text-xs text-slate-500">© {{ date('Y') }} Ceysaid Holidays (Pvt) Ltd. Internal business management workspace.</p></div>
    </footer>
</body>
</html>
