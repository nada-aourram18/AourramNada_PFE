@extends('layouts.app')

@section('title', $appointment->appointment_uid)
@section('heading', $appointment->appointment_uid)
@section('subheading', $appointment->patient?->full_name)

@section('content')
<div class="mx-auto max-w-2xl rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
    <dl class="space-y-3 text-sm">
        <div class="flex justify-between gap-4 border-b border-gray-50 py-2"><dt class="text-slate-500">Date</dt><dd class="font-semibold text-stone-900">{{ $appointment->appointment_date?->format('d/m/Y') }} {{ substr((string) $appointment->appointment_time, 0, 5) }}</dd></div>
        <div class="flex justify-between gap-4 border-b border-gray-50 py-2"><dt class="text-slate-500">Statut</dt><dd><x-badge :status="$appointment->status">{{ __('messages.status.'.$appointment->status) }}</x-badge></dd></div>
    </dl>
    <div class="mt-6 flex flex-wrap gap-2">
        <a href="{{ route('appointments.edit', $appointment) }}" class="rounded-xl bg-medical px-4 py-2.5 text-sm font-bold text-white shadow-md shadow-medical/20 transition-all duration-200 hover:bg-medical-dark active:scale-[0.98]">{{ __('messages.edit') }}</a>
        <a href="{{ route('appointments.index') }}" class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition-all duration-200 hover:bg-gray-50 active:scale-[0.98]">Retour</a>
    </div>
</div>
@endsection
