<?php

namespace App\Repositories;

use App\Models\Appointment;
use App\Services\AirtableClient;
use App\Support\DoctorScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AppointmentRepository
{
    /** @var array{name: string, is_link: bool}|null */
    protected ?array $patientFieldMetaCache = null;

    /** @var array{name: string, is_link: bool}|null */
    protected ?array $ownerFieldMetaCache = null;

    /** @var array<int, array<string, mixed>>|null */
    protected ?array $baseTablesCache = null;

    /** @var array<int, array<string, mixed>>|null */
    protected ?array $appointmentsFieldsCache = null;

    /** @var array<string, string> */
    protected array $appointmentColumnCache = [];

    public function __construct(
        protected AirtableClient $client,
        protected PatientRepository $patients,
        protected DoctorScope $doctorScope,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function baseTables(): array
    {
        return $this->baseTablesCache ??= $this->client->fetchBaseTables();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function appointmentsFields(): array
    {
        if ($this->appointmentsFieldsCache !== null) {
            return $this->appointmentsFieldsCache;
        }
        $appointmentsTableName = (string) config('services.airtable.tables.appointments', 'Appointments');
        foreach ($this->baseTables() as $t) {
            if (($t['name'] ?? '') === $appointmentsTableName) {
                return $this->appointmentsFieldsCache = $t['fields'] ?? [];
            }
        }

        return $this->appointmentsFieldsCache = [];
    }

    /**
     * @return array<string, true>
     */
    protected function appointmentsTableFieldNameSet(): array
    {
        $set = [];
        foreach ($this->appointmentsFields() as $f) {
            $n = trim((string) ($f['name'] ?? ''));
            if ($n !== '') {
                $set[$n] = true;
            }
        }

        return $set;
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    protected function filterFieldsForAppointmentsTable(array $fields): array
    {
        $allowed = $this->appointmentsTableFieldNameSet();
        if ($allowed === []) {
            return $fields;
        }

        return array_filter(
            $fields,
            fn ($_, string $key) => isset($allowed[$key]),
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * Nom de colonne Airtable pour un attribut logique du modèle (date, heure, statut, etc.).
     */
    protected function appointmentColumn(string $logical): string
    {
        if (isset($this->appointmentColumnCache[$logical])) {
            return $this->appointmentColumnCache[$logical];
        }

        $override = trim((string) config('services.airtable.appointment_column_overrides.'.$logical, ''));
        if ($override !== '') {
            return $this->appointmentColumnCache[$logical] = $override;
        }

        foreach ($this->appointmentColumnCandidates($logical) as $candidate) {
            foreach ($this->appointmentsFields() as $field) {
                $name = (string) ($field['name'] ?? '');
                if ($name !== '' && strcasecmp(trim($name), trim($candidate)) === 0) {
                    return $this->appointmentColumnCache[$logical] = $name;
                }
            }
        }

        return $this->appointmentColumnCache[$logical] = $logical;
    }

    /**
     * @return list<string>
     */
    protected function appointmentColumnCandidates(string $logical): array
    {
        return match ($logical) {
            'appointment_date' => [
                '. Date du rendez-vous',
                'Date du rendez-vous',
                'appointment_date',
                'Appointment date',
            ],
            'appointment_time' => ['Heure du rendez-vous', 'appointment_time', 'Appointment time'],
            'consultation_type' => ['Type de consultation', 'consultation_type', 'Consultation type'],
            'status' => [' Statut', 'Statut', 'status', 'Status'],
            'google_calendar_event_id' => [
                'Google Calendar',
                'google_calendar_event_id',
                'Google Calendar ID',
                'ID Google Calendar',
            ],
            'appointment_uid' => ['appointment_id', 'appointment_uid', 'UID rendez-vous', 'Appointment UID'],
            default => [$logical],
        };
    }

    protected function appointmentTimeFieldType(): ?string
    {
        $resolved = trim($this->appointmentColumn('appointment_time'));
        foreach ($this->appointmentsFields() as $field) {
            $n = trim((string) ($field['name'] ?? ''));
            if ($n !== '' && strcasecmp($n, $resolved) === 0) {
                return (string) ($field['type'] ?? '');
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    protected function singleSelectChoiceNamesForLogical(string $logical): array
    {
        $col = trim($this->appointmentColumn($logical));
        foreach ($this->appointmentsFields() as $field) {
            $n = trim((string) ($field['name'] ?? ''));
            if ($n === '' || strcasecmp($n, $col) !== 0) {
                continue;
            }
            if (($field['type'] ?? '') !== 'singleSelect') {
                return [];
            }
            $names = [];
            foreach (data_get($field, 'options.choices', []) as $choice) {
                $n = trim((string) ($choice['name'] ?? ''));
                if ($n !== '') {
                    $names[] = $n;
                }
            }

            return $names;
        }

        return [];
    }

    /**
     * Valeur à envoyer pour un champ single select (libellé exact attendu par Airtable).
     */
    protected function appointmentSelectValueForWrite(string $logical, string $value): string
    {
        $choices = $this->singleSelectChoiceNamesForLogical($logical);
        if ($choices === []) {
            return $value;
        }
        if (in_array($value, $choices, true)) {
            return $value;
        }

        if ($logical === 'consultation_type') {
            $mapped = $this->mapConsultationTypeToAirtableChoice($value, $choices);
            if ($mapped !== null) {
                return $mapped;
            }
        }

        $keys = $logical === 'status'
            ? ['confirme', 'en_attente', 'annule']
            : ['general', 'dentaire', 'autre'];

        foreach ($keys as $key) {
            if ($key !== $value) {
                continue;
            }
            $msgKey = $logical === 'status' ? 'messages.status.'.$key : 'messages.consultation.'.$key;
            foreach (['fr', 'en', 'ar'] as $loc) {
                $label = trans($msgKey, [], $loc);
                foreach ($choices as $c) {
                    if (strcasecmp(trim($c), trim($label)) === 0) {
                        return $c;
                    }
                }
            }
        }

        foreach ($choices as $c) {
            if (strcasecmp($c, $value) === 0) {
                return $c;
            }
        }

        $needle = Str::slug($value, '_');
        foreach ($choices as $c) {
            if (Str::slug($c, '_') === $needle) {
                return $c;
            }
        }

        foreach ($keys as $key) {
            if ($key !== $value) {
                continue;
            }
            $msgKey = $logical === 'status' ? 'messages.status.'.$key : 'messages.consultation.'.$key;
            foreach (['fr', 'en', 'ar'] as $loc) {
                $labSlug = Str::slug(trans($msgKey, [], $loc), '_');
                foreach ($choices as $c) {
                    if (Str::slug($c, '_') === $labSlug) {
                        return $c;
                    }
                }
            }
        }

        return $value;
    }

    /**
     * Bases FR souvent « Dentiste | Général » au lieu de general | dentaire | autre.
     *
     * @param  list<string>  $choices
     */
    protected function mapConsultationTypeToAirtableChoice(string $value, array $choices): ?string
    {
        $norm = fn (string $s) => Str::lower(trim(Str::ascii($s)));

        $pickContains = function (array $hints) use ($choices, $norm): ?string {
            foreach ($choices as $c) {
                $lc = $norm($c);
                foreach ($hints as $h) {
                    if (str_contains($lc, $norm($h))) {
                        return $c;
                    }
                }
            }

            return null;
        };

        if ($value === 'dentaire') {
            $p = $pickContains(['dent', 'dentiste', 'dental']);
            if ($p !== null) {
                return $p;
            }
        }

        if ($value === 'general') {
            $p = $pickContains(['géné', 'general', 'gen']);
            if ($p !== null) {
                return $p;
            }
        }

        if ($value === 'autre') {
            $p = $pickContains(['autre', 'other', 'divers']);
            if ($p !== null) {
                return $p;
            }
            foreach ($choices as $c) {
                if ($norm($c) === $norm('Général') || str_contains($norm($c), 'general') || str_contains($norm($c), 'gene')) {
                    return $c;
                }
            }
        }

        return null;
    }

    /**
     * Remappe une option Airtable vers le code interne (slug) utilisé par l’app.
     */
    protected function appointmentSelectValueFromRecord(string $logical, string $stored): string
    {
        $allowed = $logical === 'status'
            ? ['confirme', 'en_attente', 'annule']
            : ['general', 'dentaire', 'autre'];

        if (in_array($stored, $allowed, true)) {
            return $stored;
        }

        foreach ($allowed as $key) {
            $label = trans(
                $logical === 'status' ? 'messages.status.'.$key : 'messages.consultation.'.$key,
                [],
                'fr'
            );
            if (strcasecmp(trim($stored), trim($label)) === 0) {
                return $key;
            }
        }

        foreach ($allowed as $key) {
            if (strcasecmp($stored, $key) === 0) {
                return $key;
            }
        }

        if ($logical === 'consultation_type') {
            $ls = Str::lower(trim(Str::ascii($stored)));
            if (str_contains($ls, 'dent')) {
                return 'dentaire';
            }
            if (str_contains($ls, 'gene') || str_contains($ls, 'general')) {
                return 'general';
            }
        }

        return $stored;
    }

    protected function coerceTimeToHms(string $time): string
    {
        $time = trim($time);
        if ($time === '') {
            return '09:00:00';
        }
        if (strlen($time) === 5 && str_contains($time, ':')) {
            return $time.':00';
        }

        return strlen($time) >= 8 ? substr($time, 0, 8) : $time;
    }

    /**
     * Valeur à envoyer à Airtable pour appointment_time (texte H:i:s ou ISO si colonne dateTime).
     */
    protected function appointmentTimeForAirtableWrite(string $dateYmd, string $timeAny): mixed
    {
        $hms = $this->coerceTimeToHms($timeAny);
        if ($this->appointmentTimeFieldType() === 'dateTime') {
            $tz = (string) config('app.timezone', 'UTC');

            return Carbon::parse($dateYmd.' '.$hms, $tz)->toIso8601String();
        }

        return $hms;
    }

    protected function normalizeAppointmentTimeFromRecord(mixed $timeRaw): string
    {
        if (! is_string($timeRaw) || $timeRaw === '') {
            return '09:00:00';
        }
        if (str_contains($timeRaw, 'T') || preg_match('/^\d{4}-\d{2}-\d{2}/', $timeRaw)) {
            try {
                return Carbon::parse($timeRaw)->format('H:i:s');
            } catch (\Throwable) {
                return '09:00:00';
            }
        }

        return $this->coerceTimeToHms($timeRaw);
    }

    /**
     * Champ Airtable qui relie (ou identifie) le patient pour un RDV.
     *
     * @return array{name: string, is_link: bool}
     */
    protected function resolvePatientField(): array
    {
        if ($this->patientFieldMetaCache !== null) {
            return $this->patientFieldMetaCache;
        }

        $fromEnv = trim((string) config('services.airtable.fields.appointment_patient_link', ''));
        $patientsTableName = (string) config('services.airtable.tables.patients', 'Patients');
        $appointmentsTableName = (string) config('services.airtable.tables.appointments', 'Appointments');
        $tables = $this->baseTables();

        $patientsTableId = null;
        foreach ($tables as $t) {
            if (($t['name'] ?? '') === $patientsTableName) {
                $patientsTableId = $t['id'] ?? null;
                break;
            }
        }

        $appointmentsTable = null;
        foreach ($tables as $t) {
            if (($t['name'] ?? '') === $appointmentsTableName) {
                $appointmentsTable = $t;
                break;
            }
        }

        if ($appointmentsTable === null) {
            return $this->patientFieldMetaCache = ['name' => 'patient_id', 'is_link' => true];
        }

        $fields = $appointmentsTable['fields'] ?? [];

        if ($fromEnv !== '') {
            foreach ($fields as $field) {
                if (($field['name'] ?? '') === $fromEnv) {
                    $isLink = (($field['type'] ?? '') === 'multipleRecordLinks');

                    return $this->patientFieldMetaCache = ['name' => $fromEnv, 'is_link' => $isLink];
                }
            }

            return $this->patientFieldMetaCache = ['name' => $fromEnv, 'is_link' => true];
        }

        if ($patientsTableId !== null) {
            foreach ($fields as $field) {
                if (($field['type'] ?? '') !== 'multipleRecordLinks') {
                    continue;
                }
                $linked = data_get($field, 'options.linkedTableId') ?? data_get($field, 'options.LinkedTableId');
                if ($linked === $patientsTableId) {
                    return $this->patientFieldMetaCache = [
                        'name' => (string) ($field['name'] ?? 'patient_id'),
                        'is_link' => true,
                    ];
                }
            }
        }

        foreach ($fields as $field) {
            if (($field['type'] ?? '') !== 'singleLineText') {
                continue;
            }
            $n = trim((string) ($field['name'] ?? ''));
            if (strcasecmp($n, 'Patient ID') === 0) {
                return $this->patientFieldMetaCache = ['name' => (string) ($field['name'] ?? 'Patient ID'), 'is_link' => false];
            }
        }

        foreach ($fields as $field) {
            if (($field['type'] ?? '') !== 'singleLineText') {
                continue;
            }
            $n = Str::lower((string) ($field['name'] ?? ''));
            if (str_contains($n, 'patient')) {
                return $this->patientFieldMetaCache = [
                    'name' => (string) ($field['name'] ?? 'patient_id'),
                    'is_link' => false,
                ];
            }
        }

        return $this->patientFieldMetaCache = ['name' => 'patient_id', 'is_link' => true];
    }

    /**
     * Champ Airtable propriétaire du rendez-vous (doctor / owner / user).
     *
     * @return array{name: string, is_link: bool}|null
     */
    protected function resolveOwnerField(): ?array
    {
        if ($this->ownerFieldMetaCache !== null) {
            return $this->ownerFieldMetaCache;
        }

        $appointmentsTableName = (string) config('services.airtable.tables.appointments', 'Appointments');
        $usersTableName = (string) config('services.airtable.tables.users', 'users');
        $tables = $this->baseTables();

        $usersTableId = null;
        $appointmentsTable = null;
        foreach ($tables as $t) {
            if (($t['name'] ?? '') === $usersTableName) {
                $usersTableId = $t['id'] ?? null;
            }
            if (($t['name'] ?? '') === $appointmentsTableName) {
                $appointmentsTable = $t;
            }
        }
        if ($appointmentsTable === null) {
            return $this->ownerFieldMetaCache = null;
        }

        $fields = $appointmentsTable['fields'] ?? [];
        $candidates = ['doctor', 'owner_user_id', 'user_id', 'owner', 'receptionist'];

        if ($usersTableId !== null) {
            foreach ($fields as $field) {
                if (($field['type'] ?? '') !== 'multipleRecordLinks') {
                    continue;
                }
                $linked = data_get($field, 'options.linkedTableId') ?? data_get($field, 'options.LinkedTableId');
                if ($linked === $usersTableId) {
                    return $this->ownerFieldMetaCache = [
                        'name' => (string) ($field['name'] ?? 'doctor'),
                        'is_link' => true,
                    ];
                }
            }
        }

        foreach ($fields as $field) {
            $name = trim((string) ($field['name'] ?? ''));
            foreach ($candidates as $candidate) {
                if (strcasecmp($name, $candidate) === 0) {
                    return $this->ownerFieldMetaCache = [
                        'name' => $name,
                        'is_link' => (($field['type'] ?? '') === 'multipleRecordLinks'),
                    ];
                }
            }
        }

        return $this->ownerFieldMetaCache = null;
    }

    protected function currentUserRecordId(): ?string
    {
        $id = auth()->user()?->id;

        return is_string($id) && $id !== '' ? $id : null;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    protected function recordOwnedByCurrentUser(array $record): bool
    {
        return $this->doctorScope->appointmentBelongsToDoctor($record);
    }

    /**
     * @param  array{name: string, is_link: bool}  $pf
     */
    protected function patientFieldWriteValue(array $pf, string $patientRecordId): mixed
    {
        if ($patientRecordId === '') {
            return $pf['is_link'] ? [] : '';
        }

        return $pf['is_link'] ? [$patientRecordId] : $patientRecordId;
    }

    public function find(string $id): ?Appointment
    {
        try {
            $rec = $this->client->getRecord(AirtableClient::TABLE_APPOINTMENTS, $id);
            if (! $this->recordOwnedByCurrentUser($rec)) {
                return null;
            }

            return $this->mapFromRecord($rec, true);
        } catch (\Throwable) {
            return null;
        }
    }

    public function findOrFail(string $id): Appointment
    {
        $a = $this->find($id);
        if ($a === null) {
            throw (new ModelNotFoundException)->setModel(Appointment::class, [$id]);
        }

        return $a;
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  Collection<string, \App\Models\Patient>|null  $patientsById
     * @param  Collection<string, \App\Models\Patient>|null  $patientsByUid
     */
    public function mapFromRecord(
        array $record,
        bool $withPatient = false,
        ?Collection $patientsById = null,
        ?Collection $patientsByUid = null,
    ): Appointment {
        $f = $record['fields'] ?? [];
        $pf = $this->resolvePatientField();
        $name = $pf['name'];
        $raw = $f[$name] ?? null;
        $patientId = '';
        if ($pf['is_link']) {
            $patientId = is_array($raw) && $raw !== [] ? (string) $raw[0] : '';
        } elseif (is_string($raw) && $raw !== '') {
            if (str_starts_with($raw, 'rec')) {
                $patientId = $raw;
            } elseif ($patientsByUid !== null) {
                $patientId = $patientsByUid->get($raw)?->id ?? '';
            } else {
                $byUid = $this->patients->findByPatientUid($raw);
                $patientId = $byUid?->id ?? '';
            }
        }

        $dateKey = $this->appointmentColumn('appointment_date');
        $timeKey = $this->appointmentColumn('appointment_time');
        $dateRaw = $f[$dateKey] ?? null;
        $date = $dateRaw
            ? Carbon::parse(is_string($dateRaw) ? $dateRaw : (string) $dateRaw)
            : Carbon::today();

        $time = $this->normalizeAppointmentTimeFromRecord($f[$timeKey] ?? '09:00');

        $patient = null;
        if ($withPatient && $patientId !== '') {
            $patient = $patientsById?->get($patientId) ?? $this->patients->find($patientId);
        }

        $uidKey = $this->appointmentColumn('appointment_uid');
        $typeKey = $this->appointmentColumn('consultation_type');
        $statusKey = $this->appointmentColumn('status');
        $gcalKey = $this->appointmentColumn('google_calendar_event_id');

        $typeRaw = trim((string) ($f[$typeKey] ?? ''));
        $statusRaw = trim((string) ($f[$statusKey] ?? ''));

        return new Appointment(
            id: $record['id'],
            appointment_uid: (string) ($f[$uidKey] ?? ''),
            patient_id: $patientId,
            appointment_date: $date,
            appointment_time: $time,
            consultation_type: $this->appointmentSelectValueFromRecord(
                'consultation_type',
                $typeRaw !== '' ? $typeRaw : 'general'
            ),
            status: $this->appointmentSelectValueFromRecord(
                'status',
                $statusRaw !== '' ? $statusRaw : 'en_attente'
            ),
            google_calendar_event_id: isset($f[$gcalKey]) ? (string) $f[$gcalKey] : null,
            patient: $patient,
        );
    }

    /**
     * @return Collection<int, Appointment>
     */
    public function allWithPatients(): Collection
    {
        $rows = $this->client->listAllRecordRows(AirtableClient::TABLE_APPOINTMENTS);
        $rows = array_values(array_filter($rows, fn (array $r) => $this->recordOwnedByCurrentUser($r)));

        // One patients list for the whole batch (avoids N+1 Airtable GETs that timeout the calendar).
        $patients = $this->patients->allAsCollection();
        $patientsById = $patients->keyBy(fn ($p) => $p->id);
        $patientsByUid = $patients
            ->filter(fn ($p) => $p->patient_uid !== '')
            ->keyBy(fn ($p) => $p->patient_uid);

        return collect($rows)
            ->map(fn (array $r) => $this->mapFromRecord($r, true, $patientsById, $patientsByUid))
            ->values();
    }

    /**
     * @return LengthAwarePaginator<int, Appointment>
     */
    public function paginateIndex(int $page, int $perPage, ?string $date, ?string $status): LengthAwarePaginator
    {
        $items = $this->allWithPatients()->filter(function (Appointment $a) use ($date, $status) {
            if ($date !== null && $date !== '' && $a->appointment_date->toDateString() !== $date) {
                return false;
            }
            if ($status !== null && $status !== '' && $a->status !== $status) {
                return false;
            }

            return true;
        })->values()->sortByDesc(fn (Appointment $a) => $a->startsAt()->timestamp)->values();

        $total = $items->count();
        $slice = $items->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * @return Collection<int, Appointment>
     */
    public function forPatient(string $patientId): Collection
    {
        return $this->allWithPatients()
            ->filter(fn (Appointment $a) => $a->patient_id === $patientId)
            ->sortByDesc(fn (Appointment $a) => $a->startsAt()->timestamp)
            ->values();
    }

    /**
     * @return LengthAwarePaginator<int, Appointment>
     */
    public function paginateForPatient(string $patientId, int $page, int $perPage): LengthAwarePaginator
    {
        $items = $this->forPatient($patientId);
        $total = $items->count();
        $slice = $items->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * @return Collection<int, Appointment>
     */
    public function forCalendar(?Carbon $start, ?Carbon $end, string $q = ''): Collection
    {
        return $this->allWithPatients()->filter(function (Appointment $a) use ($start, $end, $q) {
            if ($start && $end) {
                $d = $a->appointment_date->toDateString();
                // FullCalendar end is exclusive.
                if ($d < $start->toDateString() || $d >= $end->toDateString()) {
                    return false;
                }
            }
            if ($q !== '') {
                $name = Str::lower($a->patient?->full_name ?? '');

                return Str::contains($name, Str::lower($q));
            }

            return true;
        })->values();
    }

    public function update(Appointment $appointment, array $data): Appointment
    {
        $pf = $this->resolvePatientField();
        $ownerField = $this->resolveOwnerField();
        $ownerId = $this->currentUserRecordId();
        $patientId = isset($data['patient_id'])
            ? (string) $data['patient_id']
            : $appointment->patient_id;

        $dateStr = isset($data['appointment_date'])
            ? Carbon::parse((string) $data['appointment_date'])->toDateString()
            : $appointment->appointment_date->toDateString();
        $timeAny = isset($data['appointment_time'])
            ? (string) $data['appointment_time']
            : (string) $appointment->appointment_time;

        $gcalVal = array_key_exists('google_calendar_event_id', $data)
            ? $data['google_calendar_event_id']
            : $appointment->google_calendar_event_id;

        $fields = [
            $pf['name'] => $this->patientFieldWriteValue($pf, $patientId),
            $this->appointmentColumn('appointment_date') => $dateStr,
            $this->appointmentColumn('appointment_time') => $this->appointmentTimeForAirtableWrite($dateStr, $timeAny),
            $this->appointmentColumn('consultation_type') => $this->appointmentSelectValueForWrite(
                'consultation_type',
                (string) ($data['consultation_type'] ?? $appointment->consultation_type)
            ),
            $this->appointmentColumn('status') => $this->appointmentSelectValueForWrite(
                'status',
                (string) ($data['status'] ?? $appointment->status)
            ),
            $this->appointmentColumn('google_calendar_event_id') => $gcalVal,
        ];
        $this->applyDoctorFieldOnWrite($fields, $ownerField, $ownerId);
        $fields = array_filter($fields, fn ($v) => $v !== null);
        $fields = $this->filterFieldsForAppointmentsTable($fields);

        $rec = $this->client->updateRecord(AirtableClient::TABLE_APPOINTMENTS, $appointment->id, $fields);

        return $this->mapFromRecord($rec, true);
    }

    public function delete(string $id): void
    {
        $this->client->deleteRecords(AirtableClient::TABLE_APPOINTMENTS, [$id]);
    }

    /**
     * @param  array{
     *     patient_id: string,
     *     appointment_date: string|\DateTimeInterface,
     *     appointment_time?: string,
     *     consultation_type?: string,
     *     status?: string,
     *     google_calendar_event_id?: string|null
     * }  $data
     */
    public function create(array $data): Appointment
    {
        $pf = $this->resolvePatientField();
        $ownerField = $this->resolveOwnerField();
        $ownerId = $this->currentUserRecordId();
        $patientId = (string) ($data['patient_id'] ?? '');
        $uid = $this->generateUid();

        $date = Carbon::parse($data['appointment_date'])->toDateString();
        $time = (string) ($data['appointment_time'] ?? '09:00');

        $fields = [
            $pf['name'] => $this->patientFieldWriteValue($pf, $patientId),
            $this->appointmentColumn('appointment_date') => $date,
            $this->appointmentColumn('appointment_time') => $this->appointmentTimeForAirtableWrite($date, $time),
            $this->appointmentColumn('consultation_type') => $this->appointmentSelectValueForWrite(
                'consultation_type',
                (string) ($data['consultation_type'] ?? 'general')
            ),
            $this->appointmentColumn('status') => $this->appointmentSelectValueForWrite(
                'status',
                (string) ($data['status'] ?? 'en_attente')
            ),
        ];
        $this->applyDoctorFieldOnWrite($fields, $ownerField, $ownerId);
        if (! empty($data['google_calendar_event_id'])) {
            $fields[$this->appointmentColumn('google_calendar_event_id')] = (string) $data['google_calendar_event_id'];
        }

        if ($this->shouldWriteAppointmentUid()) {
            $fields[$this->appointmentColumn('appointment_uid')] = $uid;
        }

        $fields = $this->filterFieldsForAppointmentsTable($fields);

        $resp = $this->client->createRecords(AirtableClient::TABLE_APPOINTMENTS, [
            ['fields' => $fields],
        ]);
        $records = $resp['records'] ?? [];
        if ($records === []) {
            throw new \RuntimeException('Airtable: création rendez-vous impossible.');
        }

        return $this->mapFromRecord($records[0], true);
    }

    /**
     * @param  array<string, mixed>  $fields
     * @param  array{name: string, is_link: bool}|null  $ownerField
     */
    protected function applyDoctorFieldOnWrite(array &$fields, ?array $ownerField, ?string $ownerId): void
    {
        if ($ownerId === null || $ownerId === '') {
            return;
        }

        $doctorField = $this->doctorScope->appointmentDoctorField();
        if ($doctorField !== null) {
            $fields[$doctorField['name']] = $doctorField['is_link'] ? [$ownerId] : $ownerId;

            return;
        }

        if ($ownerField !== null) {
            $fields[$ownerField['name']] = $ownerField['is_link'] ? [$ownerId] : $ownerId;
        }
    }

    protected function shouldWriteAppointmentUid(): bool
    {
        $col = trim($this->appointmentColumn('appointment_uid'));
        foreach ($this->appointmentsFields() as $field) {
            if (($field['name'] ?? '') !== $col) {
                continue;
            }
            $type = (string) ($field['type'] ?? '');

            return ! in_array($type, ['autoNumber', 'formula', 'createdTime'], true);
        }

        return true;
    }

    protected function generateUid(): string
    {
        $existing = $this->allWithPatients()->pluck('appointment_uid')->all();
        do {
            $uid = 'APT-'.strtoupper(Str::random(8));
        } while (in_array($uid, $existing, true));

        return $uid;
    }
}
