@if(session('success'))
    <div class="mb-6 flex items-start gap-3 rounded-2xl border border-emerald-100 bg-white px-4 py-3 text-sm text-slate-800 shadow-soft ring-1 ring-emerald-600/10">
        <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white">✓</span>
        <span class="pt-0.5 font-medium">{{ session('success') }}</span>
    </div>
@endif
@if(session('error'))
    <div class="mb-6 flex items-start gap-3 rounded-2xl border border-red-100 bg-red-50/50 px-4 py-3 text-sm text-red-900 shadow-sm">
        <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-red-200 bg-white text-red-600">!</span>
        <span class="pt-0.5 font-medium">{{ session('error') }}</span>
    </div>
@endif
