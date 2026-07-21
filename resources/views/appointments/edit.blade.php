@extends('layouts.app')

@section('title', __('messages.edit'))
@section('heading', __('messages.edit'))
@section('subheading', $appointment->appointment_uid)

@section('content')
<form method="post" action="{{ route('appointments.update',$appointment) }}" class="max-w-3xl space-y-4 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
    @csrf @method('PUT')
    @include('appointments._form', ['appointment' => $appointment])
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
    let t;
    async function loadPatients() {
        const url = new URL(@json(route('appointments.patients.search')));
        url.searchParams.set('q', q.value.trim());
        const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
        const json = await res.json();
        const current = @json($appointment->patient_id);
        sel.innerHTML = '';
        (json.data || []).forEach(p => {
            const o = document.createElement('option');
            o.value = p.id;
            o.textContent = `${p.full_name} (${p.patient_uid})`;
            if (String(p.id) === String(current)) o.selected = true;
            sel.appendChild(o);
        });
        if (!sel.options.length) {
            const o = document.createElement('option');
            o.value = current;
            o.textContent = @json($appointment->patient?->full_name.' ('.$appointment->patient?->patient_uid.')');
            o.selected = true;
            sel.appendChild(o);
        }
    }
    q.addEventListener('input', () => { clearTimeout(t); t = setTimeout(loadPatients, 250); });
</script>
@endpush
