@extends('layouts.app')

@section('title', __('messages.nav.appointments'))
@section('heading', __('messages.nav.appointments'))
@section('subheading', 'Planification & suivi des consultations')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <form method="get" class="flex flex-1 flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            <div class="flex flex-wrap items-center gap-2 rounded-2xl border border-gray-100 bg-white p-1 shadow-sm">
                <input type="date" name="date" value="{{ request('date') }}" class="rounded-xl border-0 bg-transparent px-3 py-2 text-sm text-slate-800 focus:ring-0">
            </div>
            <div class="flex flex-wrap gap-2 rounded-2xl border border-gray-100 bg-white p-1 shadow-sm">
                <select name="status" class="rounded-xl border-0 bg-transparent px-3 py-2 text-sm focus:ring-0">
                    <option value="">Statut</option>
                    @foreach(['confirme','en_attente','annule'] as $s)
                        <option value="{{ $s }}" @selected(request('status')==$s)>{{ __('messages.status.'.$s) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-stone-800 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:bg-stone-900 active:scale-[0.98]">{{ __('messages.filters') }}</button>
        </form>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-slate-50/80 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <th class="px-4 py-3">RDV</th>
                        <th class="px-4 py-3">Patient</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Heure</th>
                        <th class="px-4 py-3">Statut</th>
                        <th class="px-4 py-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($appointments as $i => $a)
                        <tr class="transition-colors duration-200 hover:bg-gray-50 {{ $i % 2 === 1 ? 'bg-slate-50/40' : '' }}">
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-600">{{ $a->appointment_uid }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $a->patient?->full_name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $a->appointment_date?->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ substr((string) $a->appointment_time, 0, 5) }}</td>
                            <td class="px-4 py-3"><x-badge :status="$a->status">{{ __('messages.status.'.$a->status) }}</x-badge></td>
                            <td class="px-4 py-3">
                                <div class="flex flex-col items-end gap-2">
                                    <a
                                        href="{{ route('appointments.edit', $a) }}"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-teal-100 bg-teal-50 px-3 py-1.5 text-xs font-semibold text-teal-700 transition-all duration-200 hover:bg-teal-100 active:scale-95"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                                        {{ __('messages.edit') }}
                                    </a>
                                    <div class="flex flex-wrap justify-end gap-1.5">
                                        @foreach(['confirme' => 'Conf.', 'en_attente' => 'Att.', 'annule' => 'Ann.'] as $st => $lab)
                                            <form method="post" action="{{ route('appointments.status', $a) }}" class="inline">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="{{ $st }}">
                                                <button
                                                    type="submit"
                                                    class="rounded-lg px-2.5 py-1 text-[11px] font-semibold transition-all duration-200 active:scale-95 {{ $a->status === $st ? 'border border-teal-200 bg-teal-100 text-teal-800 shadow-sm' : 'border border-gray-200 bg-white text-slate-600 hover:border-teal-200 hover:bg-teal-50 hover:text-teal-800' }}"
                                                >{{ $lab }}</button>
                                            </form>
                                        @endforeach
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-100 px-4 py-3">{{ $appointments->links() }}</div>
    </div>

</div>
@endsection
