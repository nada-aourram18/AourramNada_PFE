@props([
    'user',
    'size' => 'md',
])

@php
    $dims = [
        'xs' => 'h-7 w-7 min-h-7 min-w-7 text-[10px]',
        'sm' => 'h-9 w-9 min-h-9 min-w-9 text-xs',
        'md' => 'h-10 w-10 min-h-10 min-w-10 text-sm',
        'lg' => 'h-20 w-20 min-h-20 min-w-20 text-2xl',
    ];
    $dim = $dims[$size] ?? $dims['md'];
    $initial = strtoupper(mb_substr($user->name, 0, 1));
@endphp

@if($user->hasAvatar())
    <img
        {{ $attributes->merge(['class' => "{$dim} shrink-0 rounded-full object-cover ring-2 ring-teal-100 shadow-sm"]) }}
        src="{{ $user->avatarUrl() }}"
        alt=""
    >
@else
    <div {{ $attributes->merge(['class' => "{$dim} flex shrink-0 items-center justify-center rounded-full bg-slate-200 font-bold text-slate-700 ring-2 ring-white shadow-sm"]) }}>
        {{ $initial }}
    </div>
@endif
