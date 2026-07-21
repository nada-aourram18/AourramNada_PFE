@props([
    'title',
    'value',
    'accent' => 'blue',
])

@php
    $accentMap = [
        'blue' => ['bg' => 'bg-teal-50', 'text' => 'text-teal-700', 'ring' => 'ring-teal-600/12'],
        'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600', 'ring' => 'ring-emerald-600/15'],
        'amber' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-600', 'ring' => 'ring-amber-600/15'],
        'slate' => ['bg' => 'bg-slate-100', 'text' => 'text-slate-600', 'ring' => 'ring-slate-600/10'],
    ];
    $a = $accentMap[$accent] ?? $accentMap['blue'];
@endphp

<div {{ $attributes->merge(['class' => 'group relative overflow-hidden rounded-2xl border border-gray-100 bg-white p-6 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md']) }}>
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0 flex-1">
            <p class="text-sm font-medium text-slate-500">{{ $title }}</p>
            <p class="mt-2 text-3xl font-bold tracking-tight text-stone-900 tabular-nums">{{ $value }}</p>
        </div>
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $a['bg'] }} {{ $a['text'] }} ring-1 {{ $a['ring'] }} transition-transform duration-200 group-hover:scale-105">
            {{ $slot }}
        </div>
    </div>
</div>
