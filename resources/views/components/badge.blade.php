@props([
    'status' => null,
    'variant' => null,
])

@php
    $v = $variant ?? match ($status) {
        'confirme' => 'success',
        'en_attente' => 'warning',
        'annule' => 'danger',
        'active' => 'info',
        'cloturee' => 'neutral',
        default => 'neutral',
    };
    $classes = match ($v) {
        'success' => 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-600/20',
        'warning' => 'bg-amber-50 text-amber-800 ring-1 ring-amber-600/20',
        'danger' => 'bg-red-50 text-red-700 ring-1 ring-red-600/20',
        'info' => 'bg-teal-50 text-teal-900 ring-1 ring-teal-600/20',
        'neutral' => 'bg-slate-100 text-slate-700 ring-1 ring-slate-600/10',
        default => 'bg-slate-100 text-slate-700 ring-1 ring-slate-600/10',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {$classes}"]) }}>
    {{ $slot }}
</span>
