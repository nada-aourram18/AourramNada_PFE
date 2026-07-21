<div class="grid gap-4 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Patient</label>
        <input id="patient-q" type="search" placeholder="Rechercher nom / téléphone…" class="mb-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <select name="patient_id" id="patient_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm @error('patient_id') border-red-400 @enderror">
            @if(isset($appointment) && $appointment)
                <option value="{{ $appointment->patient_id }}" selected>{{ $appointment->patient?->full_name }} ({{ $appointment->patient?->patient_uid }})</option>
            @elseif(isset($prefillPatient) && $prefillPatient)
                <option value="{{ $prefillPatient->id }}" @selected(old('patient_id', $prefillPatient->id) == $prefillPatient->id)>{{ $prefillPatient->full_name }} ({{ $prefillPatient->patient_uid }})</option>
            @else
                <option value="" disabled @selected(! old('patient_id'))>{{ __('messages.select_patient') }}</option>
            @endif
        </select>
        @error('patient_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Date</label>
        <input type="date" name="appointment_date" value="{{ old('appointment_date', $appointment?->appointment_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm @error('appointment_date') border-red-400 @enderror">
        @error('appointment_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Heure</label>
        <input type="time" name="appointment_time" value="{{ old('appointment_time', isset($appointment) && $appointment ? substr((string) $appointment->appointment_time, 0, 5) : '09:00') }}" required
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm @error('appointment_time') border-red-400 @enderror">
        @error('appointment_time')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Statut</label>
        <select name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            @foreach(['confirme','en_attente','annule'] as $s)
                <option value="{{ $s }}" @selected(old('status', ($appointment?->status ?? 'en_attente'))==$s)>{{ __('messages.status.'.$s) }}</option>
            @endforeach
        </select>
    </div>
    <div class="sm:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Google Calendar Event ID</label>
        <input name="google_calendar_event_id" value="{{ old('google_calendar_event_id', ($appointment?->google_calendar_event_id ?? '')) }}"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono text-xs">
        @error('google_calendar_event_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
</div>
