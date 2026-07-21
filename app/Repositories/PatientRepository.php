<?php

namespace App\Repositories;

use App\Models\Patient;
use App\Services\AirtableClient;
use App\Support\AirtableFieldMap;
use App\Support\DoctorScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PatientRepository
{
    /** @var array<int, array<string, mixed>>|null */
    protected ?array $baseTablesCache = null;

    /** @var array{name: string, is_link: bool}|null */
    protected ?array $ownerFieldMetaCache = null;

    public function __construct(
        protected AirtableClient $client,
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
     * Champ Airtable propriétaire du patient (doctor / owner / user).
     *
     * @return array{name: string, is_link: bool}|null
     */
    protected function resolveOwnerField(): ?array
    {
        if ($this->ownerFieldMetaCache !== null) {
            return $this->ownerFieldMetaCache;
        }

        $patientsTableName = (string) config('services.airtable.tables.patients', 'Patients');
        $usersTableName = (string) config('services.airtable.tables.users', 'users');
        $tables = $this->baseTables();

        $usersTableId = null;
        $patientsTable = null;
        foreach ($tables as $t) {
            if (($t['name'] ?? '') === $usersTableName) {
                $usersTableId = $t['id'] ?? null;
            }
            if (($t['name'] ?? '') === $patientsTableName) {
                $patientsTable = $t;
            }
        }
        if ($patientsTable === null) {
            return $this->ownerFieldMetaCache = null;
        }

        $fields = $patientsTable['fields'] ?? [];
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

    /**
     * @return array<string, true>
     */
    protected function patientsTableFieldNameSet(): array
    {
        $patientsTableName = (string) config('services.airtable.tables.patients', 'Patients');
        foreach ($this->baseTables() as $t) {
            if (($t['name'] ?? '') !== $patientsTableName) {
                continue;
            }
            $set = [];
            foreach (($t['fields'] ?? []) as $f) {
                $n = trim((string) ($f['name'] ?? ''));
                if ($n !== '') {
                    $set[$n] = true;
                }
            }

            return $set;
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    protected function filterFieldsForPatientsTable(array $fields): array
    {
        $allowed = $this->patientsTableFieldNameSet();
        if ($allowed === []) {
            return $fields;
        }

        return array_filter(
            $fields,
            fn ($_, string $key) => isset($allowed[$key]),
            ARRAY_FILTER_USE_BOTH
        );
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
        return $this->doctorScope->patientBelongsToDoctor($record);
    }

    public function find(string $id): ?Patient
    {
        try {
            $rec = $this->client->getRecord(AirtableClient::TABLE_PATIENTS, $id);
            if (! $this->recordOwnedByCurrentUser($rec)) {
                return null;
            }

            return $this->mapFromRecord($rec);
        } catch (\Throwable) {
            return null;
        }
    }

    public function findOrFail(string $id): Patient
    {
        $p = $this->find($id);
        if ($p === null) {
            throw (new ModelNotFoundException)->setModel(Patient::class, [$id]);
        }

        return $p;
    }

    public function findByPatientUid(string $patientUid): ?Patient
    {
        $uid = trim($patientUid);
        if ($uid === '') {
            return null;
        }

        foreach ($this->allAsCollection() as $p) {
            if ($p->patient_uid === $uid) {
                return $p;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    public function mapFromRecord(array $record): Patient
    {
        $f = $record['fields'] ?? [];
        $created = isset($record['createdTime']) ? Carbon::parse((string) $record['createdTime']) : null;
        $langCol = trim(AirtableFieldMap::patientColumn('language'));
        $lang = $langCol !== '' ? (string) (AirtableFieldMap::patientValue($f, 'language') ?? 'fr') : 'fr';
        $notes = AirtableFieldMap::patientValue($f, 'notes');
        $emailCol = trim(AirtableFieldMap::patientColumn('email'));
        $emailRaw = $emailCol !== '' ? AirtableFieldMap::patientValue($f, 'email') : null;

        return new Patient(
            id: $record['id'],
            patient_uid: (string) (AirtableFieldMap::patientValue($f, 'patient_uid') ?? ''),
            full_name: (string) (AirtableFieldMap::patientValue($f, 'full_name') ?? ''),
            phone: (string) (AirtableFieldMap::patientValue($f, 'phone') ?? ''),
            language: $lang !== '' ? $lang : 'fr',
            notes: $notes !== null && $notes !== '' ? (string) $notes : null,
            created_at: $created,
            email: $emailRaw !== null && $emailRaw !== '' ? (string) $emailRaw : null,
        );
    }

    protected function shouldWritePatientUid(): bool
    {
        $col = trim(AirtableFieldMap::patientColumn('patient_uid'));
        if ($col === '') {
            return false;
        }
        $patientsTableName = (string) config('services.airtable.tables.patients', 'Patients');
        foreach ($this->baseTables() as $t) {
            if (($t['name'] ?? '') !== $patientsTableName) {
                continue;
            }
            foreach (($t['fields'] ?? []) as $field) {
                if (($field['name'] ?? '') !== $col) {
                    continue;
                }
                $type = (string) ($field['type'] ?? '');

                return ! in_array($type, ['autoNumber', 'formula', 'createdTime'], true);
            }
        }

        return true;
    }

    /**
     * @return Collection<int, Patient>
     */
    public function allAsCollection(): Collection
    {
        $rows = $this->client->listAllRecordRows(AirtableClient::TABLE_PATIENTS);
        $rows = array_values(array_filter($rows, fn (array $r) => $this->recordOwnedByCurrentUser($r)));

        return collect($rows)->map(fn (array $r) => $this->mapFromRecord($r));
    }

    /**
     * @return LengthAwarePaginator<int, Patient>
     */
    public function paginateIndex(int $page, int $perPage, string $q = ''): LengthAwarePaginator
    {
        $items = $this->allAsCollection()->filter(function (Patient $p) use ($q) {
            if ($q === '') {
                return true;
            }
            $needle = Str::lower($q);

            return Str::contains(Str::lower($p->full_name), $needle)
                || Str::contains(Str::lower($p->phone), $needle)
                || Str::contains(Str::lower($p->patient_uid), $needle);
        })->sortByDesc(fn (Patient $p) => $p->created_at?->timestamp ?? 0)->values();

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
     * @return Collection<int, Patient>
     */
    public function searchLimited(string $q, int $limit = 20): Collection
    {
        return $this->allAsCollection()->filter(function (Patient $p) use ($q) {
            if ($q === '') {
                return true;
            }
            $needle = Str::lower($q);

            return Str::contains(Str::lower($p->full_name), $needle)
                || Str::contains(Str::lower($p->phone), $needle)
                || Str::contains(Str::lower($p->patient_uid), $needle);
        })->sortBy(fn (Patient $p) => Str::lower($p->full_name))->take($limit)->values();
    }

    public function create(array $data): Patient
    {
        $logical = [
            'full_name' => (string) ($data['full_name'] ?? ''),
            'phone' => (string) ($data['phone'] ?? ''),
            'notes' => $data['notes'] ?? null,
        ];
        if (trim(AirtableFieldMap::patientColumn('email')) !== '' && ! empty($data['email'])) {
            $logical['email'] = (string) $data['email'];
        }
        $langCol = trim(AirtableFieldMap::patientColumn('language'));
        if ($langCol !== '') {
            $logical['language'] = (string) ($data['language'] ?? 'fr');
        }
        if ($this->shouldWritePatientUid()) {
            $logical['patient_uid'] = $this->generateUid();
        }
        $fields = AirtableFieldMap::mapPatientFieldsToAirtable($logical);
        $patientDoctor = $this->doctorScope->patientDoctorField();
        $doctorId = $this->doctorScope->currentDoctorRecordId();
        if ($patientDoctor !== null && $doctorId !== null && $doctorId !== '') {
            $fields[$patientDoctor['name']] = $patientDoctor['is_link'] ? [$doctorId] : $doctorId;
        } else {
            $ownerField = $this->resolveOwnerField();
            if ($ownerField !== null && $doctorId !== null && $doctorId !== '') {
                $fields[$ownerField['name']] = $ownerField['is_link'] ? [$doctorId] : $doctorId;
            }
        }
        $fields = array_filter($fields, fn ($v) => $v !== null && $v !== '');
        $fields = $this->filterFieldsForPatientsTable($fields);

        $resp = $this->client->createRecords(AirtableClient::TABLE_PATIENTS, [
            ['fields' => $fields],
        ]);
        $records = $resp['records'] ?? [];
        if ($records === []) {
            throw new \RuntimeException('Airtable: création patient impossible.');
        }

        return $this->mapFromRecord($records[0]);
    }

    public function update(Patient $patient, array $data): Patient
    {
        $logical = [
            'full_name' => (string) ($data['full_name'] ?? $patient->full_name),
            'phone' => (string) ($data['phone'] ?? $patient->phone),
        ];
        $langCol = trim(AirtableFieldMap::patientColumn('language'));
        if ($langCol !== '') {
            $logical['language'] = (string) ($data['language'] ?? $patient->language);
        }
        if (array_key_exists('notes', $data)) {
            $logical['notes'] = $data['notes'];
        }
        $fields = AirtableFieldMap::mapPatientFieldsToAirtable($logical);
        $fields = $this->filterFieldsForPatientsTable($fields);
        $rec = $this->client->updateRecord(AirtableClient::TABLE_PATIENTS, $patient->id, $fields);

        return $this->mapFromRecord($rec);
    }

    public function delete(string $id): void
    {
        $this->client->deleteRecords(AirtableClient::TABLE_PATIENTS, [$id]);
    }

    protected function generateUid(): string
    {
        $existing = $this->allAsCollection()->pluck('patient_uid')->all();
        do {
            $uid = 'PAT-'.strtoupper(Str::random(8));
        } while (in_array($uid, $existing, true));

        return $uid;
    }
}
