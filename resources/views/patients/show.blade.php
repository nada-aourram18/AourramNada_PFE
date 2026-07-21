@extends('layouts.app')

@section('title', $patient->full_name)
@section('heading', $patient->full_name)
@section('subheading', trim($patient->patient_uid.' · '.$patient->phone.($patient->email ? ' · '.$patient->email : '')))

@section('content')
@php
    $initials = '';
    foreach (array_slice(preg_split('/\s+/', trim($patient->full_name)) ?: [], 0, 2) as $w) {
        $initials .= $w !== '' ? mb_strtoupper(mb_substr($w, 0, 1)) : '';
    }
@endphp
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('appointments.create', ['patient_id' => $patient->id]) }}" class="inline-flex items-center gap-2 rounded-xl bg-medical px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:bg-medical-dark active:scale-[0.98]">{{ __('messages.new_appointment') }}</a>
        <a href="{{ route('patients.edit', $patient) }}" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition-all duration-200 hover:bg-gray-50 active:scale-[0.98]">{{ __('messages.edit') }}</a>
        <a href="{{ route('patients.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-stone-800 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:bg-stone-900 active:scale-[0.98]">Retour liste</a>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm transition-all duration-200 hover:shadow-md lg:col-span-1">
        <div class="flex flex-col items-center text-center">
            <div class="flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-teal-800 text-2xl font-bold text-white shadow-lg ring-4 ring-teal-100">
                {{ $initials ?: 'P' }}
            </div>
            <h2 class="mt-4 text-xl font-bold text-stone-900">{{ $patient->full_name }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $patient->phone }}</p>
            @if($patient->email)
                <p class="mt-1 text-sm text-teal-700">{{ $patient->email }}</p>
            @endif
            <div class="mt-3"><x-badge variant="info">{{ strtoupper($patient->language) }}</x-badge></div>
        </div>
        <dl class="mt-6 space-y-3 border-t border-gray-100 pt-6 text-sm">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Notes</dt>
                <dd class="mt-1 text-slate-800">{{ $patient->notes ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Inscrit le</dt>
                <dd class="mt-1 text-slate-600">{{ $patient->created_at?->format('d/m/Y H:i') }}</dd>
            </div>
        </dl>
    </div>

    <div class="space-y-6 lg:col-span-2">
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <h3 class="text-base font-semibold text-stone-900">Historique des rendez-vous</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="py-2 pe-4">RDV</th>
                            <th class="py-2 pe-4">Date</th>
                            <th class="py-2 pe-4">Heure</th>
                            <th class="py-2 pe-4">Type</th>
                            <th class="py-2">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($appointments as $i => $a)
                            <tr class="transition-colors duration-200 hover:bg-gray-50 {{ $i % 2 === 1 ? 'bg-slate-50/30' : '' }}">
                                <td class="py-2.5 font-mono text-xs text-slate-600">{{ $a->appointment_uid }}</td>
                                <td class="py-2.5 text-slate-700">{{ $a->appointment_date?->format('d/m/Y') }}</td>
                                <td class="py-2.5 text-slate-600">{{ substr((string) $a->appointment_time, 0, 5) }}</td>
                                <td class="py-2.5 text-slate-600">{{ __('messages.consultation.'.$a->consultation_type) }}</td>
                                <td class="py-2.5"><x-badge :status="$a->status">{{ __('messages.status.'.$a->status) }}</x-badge></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-8 text-center text-slate-500">Aucun rendez-vous</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $appointments->links() }}</div>
        </div>

        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <h3 class="text-base font-semibold text-stone-900">Activité récente</h3>
            <ol class="relative mt-4 space-y-0 border-s-2 border-teal-100 ps-6">
                @foreach($appointments->take(4) as $a)
                    <li class="relative pb-6 last:pb-0">
                        <span class="absolute -start-[1.29rem] mt-1.5 flex h-3 w-3 rounded-full border-2 border-white bg-teal-600 ring-2 ring-teal-100"></span>
                        <p class="text-sm font-medium text-slate-900">{{ __('messages.consultation.'.$a->consultation_type) }}</p>
                        <p class="text-xs text-slate-500">{{ $a->appointment_date?->format('d/m/Y') }} — <x-badge :status="$a->status">{{ __('messages.status.'.$a->status) }}</x-badge></p>
                    </li>
                @endforeach
                @if($appointments->isEmpty())
                    <li class="text-sm text-slate-500">Aucune activité à afficher.</li>
                @endif
            </ol>
        </div>
    </div>
</div>
@endsection
