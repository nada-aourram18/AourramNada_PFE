@extends('layouts.app')

@section('title', __('messages.nav.calendar'))
@section('heading', __('messages.nav.calendar'))
@section('subheading', 'Vue mensuelle des rendez-vous')

@section('content')
@if (($appointmentCount ?? 0) === 0)
    <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        Aucun rendez-vous trouvé pour ce compte dans Airtable.
    </div>
@else
    <div class="mb-4 rounded-xl border border-teal-100 bg-teal-50/80 px-4 py-3 text-sm text-teal-900">
        {{ $appointmentCount }} rendez-vous chargés depuis la base — naviguez entre les mois pour tous les voir.
    </div>
@endif

<form id="calendar-search-form" class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
    <input
        id="calendar-patient-search"
        name="q"
        type="search"
        value="{{ request('q') }}"
        placeholder="Rechercher par nom du patient..."
        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition-all duration-200 placeholder:text-slate-400 focus:border-medical focus:outline-none focus:ring-2 focus:ring-medical/20 sm:max-w-sm"
    >
    <button
        type="submit"
        class="inline-flex items-center justify-center rounded-xl bg-medical px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-medical/15 transition-all duration-200 hover:bg-medical-dark active:scale-[0.98]"
    >
        Rechercher
    </button>
</form>

<div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm sm:p-6">
    <div id="calendar" class="min-h-[640px] sm:min-h-[720px]"></div>
</div>

<div id="event-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-stone-900/50 p-4 backdrop-blur-sm">
    <div class="w-full max-w-md rounded-2xl border border-gray-100 bg-white p-6 shadow-2xl transition-all duration-200">
        <div class="flex items-start justify-between gap-3">
            <h3 id="m-title" class="text-lg font-semibold text-stone-900"></h3>
            <button type="button" id="m-close" class="rounded-lg p-1.5 text-slate-400 transition-all duration-200 hover:bg-gray-100 hover:text-slate-700 active:scale-95">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <dl class="mt-4 space-y-3 text-sm text-slate-700">
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Patient</dt><dd id="m-patient" class="mt-0.5 font-medium"></dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Téléphone</dt><dd id="m-phone" class="mt-0.5"></dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Type</dt><dd id="m-type" class="mt-0.5"></dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Statut</dt><dd id="m-status" class="mt-0.5"></dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Google ID</dt><dd id="m-google" class="mt-0.5 break-all font-mono text-xs text-slate-500"></dd></div>
        </dl>
        <div class="mt-5">
            <a id="m-link" href="#" class="inline-flex items-center justify-center rounded-xl bg-medical px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:bg-medical-dark">
                Voir le rendez-vous
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script>
    const modal = document.getElementById('event-modal');
    const close = () => { modal.classList.add('hidden'); modal.classList.remove('flex'); };
    document.getElementById('m-close').addEventListener('click', close);
    modal.addEventListener('click', (e) => { if (e.target === modal) close(); });

    document.addEventListener('DOMContentLoaded', function () {
        const calendarEl = document.getElementById('calendar');
        const searchForm = document.getElementById('calendar-search-form');
        const searchInput = document.getElementById('calendar-patient-search');
        let abortController = null;

        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            initialDate: @json($initialDate ?? now()->toDateString()),
            height: 'auto',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
            },
            buttonText: { today: "Aujourd'hui", month: 'Mois', week: 'Semaine', day: 'Jour', list: 'Liste' },
            events: function (fetchInfo, successCallback, failureCallback) {
                if (abortController) {
                    abortController.abort();
                }
                abortController = new AbortController();

                const url = new URL(@json(route('calendar.events')), window.location.origin);
                url.searchParams.set('start', fetchInfo.startStr);
                url.searchParams.set('end', fetchInfo.endStr);
                url.searchParams.set('q', searchInput?.value?.trim() ?? '');

                fetch(url.toString(), {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                    signal: abortController.signal,
                })
                    .then((res) => {
                        if (!res.ok) {
                            throw new Error('HTTP ' + res.status);
                        }
                        return res.json();
                    })
                    .then((data) => {
                        const events = Array.isArray(data) ? data : Object.values(data || {});
                        successCallback(events);
                    })
                    .catch((error) => {
                        if (error.name === 'AbortError') {
                            return;
                        }
                        failureCallback(error);
                    });
            },
            eventClick: function (info) {
                const p = info.event.extendedProps;
                document.getElementById('m-title').textContent = info.event.title;
                document.getElementById('m-patient').textContent = p.patient || '—';
                document.getElementById('m-phone').textContent = p.phone || '—';
                document.getElementById('m-type').textContent = p.type || '—';
                document.getElementById('m-status').textContent = p.status || '—';
                document.getElementById('m-google').textContent = p.google_calendar_event_id || '—';
                const link = document.getElementById('m-link');
                link.href = @json(url('/appointments')) + '/' + encodeURIComponent(info.event.id);
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            },
        });
        calendar.render();

        if (searchForm) {
            searchForm.addEventListener('submit', function (event) {
                event.preventDefault();
                calendar.refetchEvents();
            });
        }
    });
</script>
@endpush
