@extends('layouts.app')

@section('title', __('messages.nav.patients'))
@section('heading', 'Gestion des Patients')
@section('subheading', __('messages.nav.patients'))

@section('content')
<div
    x-data="{
        deleteOpen: false,
        deleteId: null,
        deleteName: '',
    }"
    class="space-y-6"
>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="relative max-w-xl flex-1">
            <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-slate-400">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
            </span>
            <input
                id="patient-search"
                type="search"
                value="{{ $q }}"
                placeholder="Rechercher par nom, téléphone ou ID…"
                class="w-full rounded-xl border border-gray-200 bg-white py-2.5 ps-10 pe-4 text-sm text-slate-900 shadow-sm transition-all duration-200 placeholder:text-slate-400 focus:border-medical focus:outline-none focus:ring-2 focus:ring-medical/20"
            >
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-slate-50/80 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">Nom</th>
                        <th class="px-4 py-3">Téléphone</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="patients-tbody" class="divide-y divide-gray-100">
                    @foreach($patients as $i => $p)
                        <tr class="transition-colors duration-200 hover:bg-gray-50 {{ $i % 2 === 1 ? 'bg-slate-50/40' : '' }}">
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-600">{{ $p->patient_uid }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $p->full_name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $p->phone }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $p->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center justify-end gap-1.5">
                                    <a
                                        href="{{ route('patients.show', $p) }}"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-teal-100 bg-teal-50 text-teal-700 transition-all duration-200 hover:bg-teal-100 active:scale-95"
                                        title="{{ __('messages.detail') }}"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                    </a>
                                    <a
                                        href="{{ route('appointments.create', ['patient_id' => $p->id]) }}"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-teal-200/80 bg-teal-50/80 text-teal-800 transition-all duration-200 hover:bg-teal-100 hover:border-teal-300 active:scale-95"
                                        title="{{ __('messages.add_appointment_for_patient') }}"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5a2.25 2.25 0 0 0 2.25-2.25m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5a2.25 2.25 0 0 1 2.25 2.25v7.5m-9-3h3.75m-3.75 3h3.75m-3.75-6h3.75m-6 3h.375m-.375 0h.375"/></svg>
                                    </a>
                                    <a
                                        href="{{ route('patients.edit', $p) }}"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-slate-600 transition-all duration-200 hover:bg-gray-50 active:scale-95"
                                        title="{{ __('messages.edit') }}"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                                    </a>
                                    @if(auth()->user()->isAdmin())
                                        <button
                                            type="button"
                                            @click="deleteId = @js($p->id); deleteName = @js($p->full_name); deleteOpen = true"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-red-100 bg-red-50 text-red-600 transition-all duration-200 hover:bg-red-100 active:scale-95"
                                            title="{{ __('messages.delete') }}"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-100 px-4 py-3">{{ $patients->links() }}</div>
    </div>

    {{-- Modal: suppression --}}
    <div x-show="deleteOpen" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4" style="display: none;">
        <div class="absolute inset-0 bg-stone-900/50 backdrop-blur-sm" @click="deleteOpen = false"></div>
        <div class="relative w-full max-w-md rounded-2xl border border-gray-100 bg-white p-6 shadow-2xl" @click.stop>
            <h3 class="text-lg font-semibold text-stone-900">Confirmer la suppression</h3>
            <p class="mt-2 text-sm text-slate-600">Supprimer définitivement <strong x-text="deleteName"></strong> ?</p>
            <form method="post" class="mt-6 flex justify-end gap-2" x-bind:action="deleteId ? `{{ url('/patients') }}/${deleteId}` : '#'">
                @csrf
                @method('DELETE')
                <button type="button" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-gray-50 active:scale-95" @click="deleteOpen = false">Annuler</button>
                <button type="submit" class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-md hover:bg-red-700 active:scale-95">{{ __('messages.delete') }}</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const input = document.getElementById('patient-search');
    const tbody = document.getElementById('patients-tbody');
    if (input && tbody) {
        const snapshot = tbody.innerHTML;
        const apptCreateUrl = @json(url('/appointments/create'));
        const apptTitle = @json(__('messages.add_appointment_for_patient'));
        const editTitle = @json(__('messages.edit'));
        const detailTitle = @json(__('messages.detail'));
        let t;
        function renderRows(items) {
            if (!items.length) {
                tbody.innerHTML = `<tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">Aucun résultat</td></tr>`;
                return;
            }
            tbody.innerHTML = items.map(p => `
                <tr class="transition-colors duration-200 hover:bg-gray-50">
                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-600">${p.patient_uid}</td>
                    <td class="px-4 py-3 font-medium text-slate-900">${p.full_name}</td>
                    <td class="px-4 py-3 text-slate-600">${p.phone}</td>
                    <td class="px-4 py-3 text-slate-500">${(p.created_at || '').replace('T',' ').slice(0,16)}</td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap items-center justify-end gap-1.5">
                        <a href="/patients/${p.id}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-teal-100 bg-teal-50 text-teal-700 hover:bg-teal-100" title="${detailTitle}">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                        </a>
                        <a href="${apptCreateUrl}?patient_id=${encodeURIComponent(p.id)}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-teal-200/80 bg-teal-50/80 text-teal-800 hover:bg-teal-100" title="${apptTitle}">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5a2.25 2.25 0 0 0 2.25-2.25m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5a2.25 2.25 0 0 1 2.25 2.25v7.5m-9-3h3.75m-3.75 3h3.75m-3.75-6h3.75m-6 3h.375m-.375 0h.375"/></svg>
                        </a>
                        <a href="/patients/${p.id}/edit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-slate-600 hover:bg-gray-50" title="${editTitle}">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                        </a>
                        </div>
                    </td>
                </tr>`).join('');
        }
        async function liveSearch() {
            const q = input.value.trim();
            const url = new URL(@json(route('patients.search')));
            url.searchParams.set('q', q);
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            renderRows(json.data || []);
        }
        input.addEventListener('input', () => {
            clearTimeout(t);
            if (!input.value.trim()) {
                tbody.innerHTML = snapshot;
                return;
            }
            t = setTimeout(liveSearch, 300);
        });
    }
</script>
@endpush
