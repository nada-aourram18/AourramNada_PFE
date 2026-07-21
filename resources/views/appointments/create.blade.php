@extends('layouts.app')

@section('title', __('messages.new_appointment'))
@section('heading', __('messages.new_appointment'))
@section('subheading', __('messages.nav.appointments'))

@section('content')
<form method="post" action="{{ route('appointments.store') }}" class="max-w-3xl space-y-4 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
    @csrf
    @include('appointments._form', ['appointment' => null, 'prefillPatient' => $prefillPatient ?? null])
    <div class="flex flex-wrap gap-2 pt-2">
        <button class="rounded-xl bg-medical px-5 py-2.5 text-sm font-bold text-white shadow-md shadow-medical/20 transition-all duration-200 hover:bg-medical-dark active:scale-[0.98]" type="submit">{{ __('messages.save') }}</button>
        <a href="{{ route('appointments.index') }}" class="rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-semibold text-slate-700 transition-all duration-200 hover:bg-gray-50 active:scale-[0.98]">{{ __('messages.cancel') }}</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
    const q = document.getElementById('patient-q');
    const sel = document.getElementById('patient_id');
    const prefill = @json($prefillPatient ? ['id' => $prefillPatient->id, 'full_name' => $prefillPatient->full_name, 'patient_uid' => $prefillPatient->patient_uid] : null);
    const current = @json((string) old('patient_id', $prefillPatient?->id ?? ''));
    let t;
    function addOption(p, selected) {
        const o = document.createElement('option');
        o.value = p.id;
        o.textContent = `${p.full_name} (${p.patient_uid})`;
        if (selected) o.selected = true;
        sel.appendChild(o);
    }
    async function loadPatients() {
        const url = new URL(@json(route('appointments.patients.search')));
        url.searchParams.set('q', q.value.trim());
        const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
        const json = await res.json();
        sel.innerHTML = '';
        const seen = new Set();
        if (prefill) {
            addOption(prefill, String(prefill.id) === String(current));
            seen.add(String(prefill.id));
        }
        (json.data || []).forEach(p => {
            if (seen.has(String(p.id))) return;
            addOption(p, String(p.id) === String(current));
            seen.add(String(p.id));
        });
        if (!sel.options.length) {
            const o = document.createElement('option');
            o.value = '';
            o.disabled = true;
            o.selected = true;
            o.textContent = @json(__('messages.select_patient'));
            sel.appendChild(o);
        }
    }
    q.addEventListener('input', () => { clearTimeout(t); t = setTimeout(loadPatients, 250); });
    document.addEventListener('DOMContentLoaded', loadPatients);
</script>
@endpush
