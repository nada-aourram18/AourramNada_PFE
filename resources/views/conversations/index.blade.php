@extends('layouts.app')

@section('title', __('messages.nav.conversations'))
@section('heading', __('messages.nav.conversations'))
@section('subheading', 'Suivi des échanges IA')

@push('head')
<style>
body { background: black !important; color: white !important; }
.lg\:pl-64 { background: black !important; }
header { background: #1f2937 !important; border-color: #374151 !important; }
header h1 { color: white !important; }
header p { color: #d1d5db !important; }
</style>
@endpush

@section('content')
@php
    $flag = fn ($l) => match ($l) {
        'ar' => '🇸🇦',
        'en' => '🇬🇧',
        default => '🇫🇷',
    };
@endphp
<div class="flex">
    <!-- Left Filters -->
    <div class="w-1/4 bg-gray-900 p-4 border-r border-gray-700">
        <h2 class="text-lg font-bold mb-4 text-teal-400">Filtres</h2>
        <form method="get" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-white">Langue</label>
                <select name="language" class="w-full rounded-xl border border-gray-600 bg-gray-800 px-3 py-2 text-sm text-white focus:border-teal-400 focus:outline-none">
                    <option value="">Toutes</option>
                    @foreach(['ar','fr','en'] as $l)
                        <option value="{{ $l }}" @selected(request('language')==$l)>{{ strtoupper($l) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-white">Statut</label>
                <select name="status" class="w-full rounded-xl border border-gray-600 bg-gray-800 px-3 py-2 text-sm text-white focus:border-teal-400 focus:outline-none">
                    <option value="">Tous</option>
                    @foreach(['active','cloturee'] as $s)
                        <option value="{{ $s }}" @selected(request('status')==$s)>{{ __('messages.conversation_status.'.$s) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="w-full rounded-xl bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:bg-teal-700 active:scale-[0.98]">Filtrer</button>
        </form>
    </div>
    <!-- Right Conversations -->
    <div class="w-3/4 p-4">
        @if($conversations->isEmpty())
            <div class="rounded-2xl border border-dashed border-gray-600 bg-gray-800 py-16 text-center text-gray-400">
                Aucune conversation pour ces filtres.
            </div>
        @else
        <div class="grid gap-4 sm:grid-cols-1 xl:grid-cols-2">
            @foreach($conversations as $c)
                @php
                    $msgs = $c->messages ?? [];
                    $last = is_array($msgs) && count($msgs) ? end($msgs) : null;
                    $preview = is_array($last) ? \Illuminate\Support\Str::limit((string) ($last['content'] ?? ''), 100) : '—';
                @endphp
                <article class="group flex flex-col rounded-2xl border border-gray-600 bg-gray-800 p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-teal-400 hover:shadow-lg">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-xl" aria-hidden="true">{{ $flag($c->language) }}</span>
                                <h2 class="truncate font-semibold text-white">{{ $c->patient?->full_name ?? 'Anonyme' }}</h2>
                            </div>
                            <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-gray-300">{{ $preview }}</p>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-gray-600 pt-4">
                        <div class="flex items-center gap-2 text-xs text-gray-400">
                            <x-badge :variant="$c->status === 'active' ? 'success' : 'neutral'">{{ __('messages.conversation_status.'.$c->status) }}</x-badge>
                            <span>{{ $c->updated_at?->diffForHumans() }}</span>
                        </div>
                        <a
                            href="{{ route('conversations.show', $c) }}"
                            class="inline-flex items-center gap-1 rounded-xl bg-teal-600 px-4 py-2 text-xs font-bold text-white shadow-md shadow-teal-600/25 transition-all duration-200 hover:bg-teal-700 active:scale-95"
                        >
                            Voir
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
        @endif

        <div class="mt-8">{{ $conversations->links() }}</div>
    </div>
</div>
@endsection
