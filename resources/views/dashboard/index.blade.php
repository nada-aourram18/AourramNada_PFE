@extends('layouts.app')

@section('title', __('messages.nav.dashboard'))
@section('heading', __('messages.nav.dashboard'))
@section('subheading', __('messages.dashboard.subtitle'))

@section('content')
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat-card :title="__('messages.dashboard.kpi_today')" :value="$appointmentsToday" accent="blue">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5a2.25 2.25 0 0 0 2.25-2.25m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5a2.25 2.25 0 0 1 2.25 2.25v7.5"/></svg>
    </x-stat-card>
    <x-stat-card :title="__('messages.dashboard.kpi_month_patients')" :value="$patientsThisMonth" accent="emerald">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a6.062 6.062 0 0 1-1.037 1.584A11.944 11.944 0 0 1 12 21a11.955 11.955 0 0 1-3.002-.21M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
    </x-stat-card>
    <x-stat-card :title="__('messages.status.confirme')" :value="$confirmed" accent="emerald">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
    </x-stat-card>
    <x-stat-card :title="__('messages.status.en_attente')" :value="$pending" accent="amber">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
    </x-stat-card>
</div>

<div class="mt-8 grid gap-6 lg:grid-cols-3">
    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm transition-all duration-200 hover:shadow-md lg:col-span-2">
        <div class="mb-4 flex items-center justify-between gap-2">
            <h2 class="text-base font-semibold text-stone-900">{{ __('messages.dashboard.chart_title') }}</h2>
            <span class="rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-semibold text-teal-700">7j</span>
        </div>
        <div class="h-64 sm:h-72">
            <canvas id="chartAppointments"></canvas>
        </div>
    </div>
    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm transition-all duration-200 hover:shadow-md">
        <h2 class="mb-4 text-base font-semibold text-stone-900">{{ __('messages.dashboard.latest') }}</h2>
        <ul class="divide-y divide-gray-100">
            @forelse($latestAppointments as $a)
                <li class="flex items-center justify-between gap-3 py-3 transition-colors duration-200 first:pt-0 hover:bg-gray-50/80">
                    <div class="min-w-0">
                        <div class="truncate font-medium text-slate-900">{{ $a->patient?->full_name ?? '—' }}</div>
                        <div class="text-xs text-slate-500">{{ $a->appointment_date?->format('d/m/Y') }} · {{ substr((string) $a->appointment_time, 0, 5) }}</div>
                    </div>
                    <x-badge :status="$a->status">{{ __('messages.status.'.$a->status) }}</x-badge>
                </li>
            @empty
                <li class="py-10 text-center text-sm text-slate-500">{{ __('messages.dashboard.no_appointments') }}</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endpush

@push('scripts')
<script>
    (function () {
        const ctx = document.getElementById('chartAppointments');
        if (!ctx) return;
        const grad = ctx.getContext('2d').createLinearGradient(0, 0, 0, 280);
        grad.addColorStop(0, 'rgba(13, 148, 136, 0.32)');
        grad.addColorStop(1, 'rgba(13, 148, 136, 0)');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($chartLabels),
                datasets: [{
                    label: 'RDV',
                    data: @json($chartData),
                    borderColor: '#0d9488',
                    backgroundColor: grad,
                    borderWidth: 2,
                    tension: 0.35,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#0d9488',
                    pointBorderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                interaction: { intersect: false, mode: 'index' },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#64748b', font: { size: 11, family: '"Plus Jakarta Sans", system-ui, sans-serif' } },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0, color: '#64748b', font: { size: 11 } },
                        grid: { color: 'rgba(148, 163, 184, 0.15)' },
                    },
                },
            },
        });
    })();
</script>
@endpush
