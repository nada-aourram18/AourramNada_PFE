<?php

namespace App\Support;

use App\Services\AirtableClient;

/**
 * Isole les données par médecin connecté (record Airtable rec… dans la table Doctors).
 */
class DoctorScope
{
    /** @var array<int, array<string, mixed>>|null */
    protected ?array $baseTablesCache = null;

    /** @var array{name: string, is_link: bool}|null */
    protected ?array $appointmentDoctorFieldCache = null;

    /** @var array{name: string, is_link: bool}|null */
    protected ?array $patientDoctorFieldCache = null;

    /** @var array<string, true>|null */
    protected ?array $patientIdsForCurrentDoctorCache = null;

    public function __construct(protected AirtableClient $client) {}

    public function currentDoctorRecordId(): ?string
    {
        $id = auth()->user()?->id;

        return is_string($id) && $id !== '' ? $id : null;
    }

    public function shouldBypassIsolation(): bool
    {
        $user = auth()->user();

        return $user === null || $user->isAdmin();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function baseTables(): array
    {
        return $this->baseTablesCache ??= $this->client->fetchBaseTables();
    }

    /**
     * Champ doctor_id (ou équivalent) sur Appointments.
     *
     * @return array{name: string, is_link: bool}|null
     */
    public function appointmentDoctorField(): ?array
    {
        if ($this->appointmentDoctorFieldCache !== null) {
            return $this->appointmentDoctorFieldCache;
        }

        $fromEnv = trim((string) config('services.airtable.fields.appointment_doctor', ''));
        if ($fromEnv !== '') {
            return $this->appointmentDoctorFieldCache = ['name' => $fromEnv, 'is_link' => true];
        }

        $appointmentsTableName = (string) config('services.airtable.tables.appointments', 'Appointments');
        $usersTableName = (string) config('services.airtable.tables.users', 'Doctors');
        $usersTableId = null;
        $appointmentsTable = null;

        foreach ($this->baseTables() as $t) {
            if (($t['name'] ?? '') === $usersTableName) {
                $usersTableId = $t['id'] ?? null;
            }
            if (($t['name'] ?? '') === $appointmentsTableName) {
                $appointmentsTable = $t;
            }
        }

        if ($appointmentsTable === null) {
            return $this->appointmentDoctorFieldCache = null;
        }

        $candidates = ['doctor_id', 'doctor', 'owner_user_id', 'user_id', 'owner', 'receptionist'];

        if ($usersTableId !== null) {
            foreach ($appointmentsTable['fields'] ?? [] as $field) {
                if (($field['type'] ?? '') !== 'multipleRecordLinks') {
                    continue;
                }
                $linked = data_get($field, 'options.linkedTableId') ?? data_get($field, 'options.LinkedTableId');
                if ($linked === $usersTableId) {
                    return $this->appointmentDoctorFieldCache = [
                        'name' => (string) ($field['name'] ?? 'doctor_id'),
                        'is_link' => true,
                    ];
                }
            }
        }

        foreach ($appointmentsTable['fields'] ?? [] as $field) {
            $name = trim((string) ($field['name'] ?? ''));
            foreach ($candidates as $candidate) {
                if (strcasecmp($name, $candidate) === 0) {
                    return $this->appointmentDoctorFieldCache = [
                        'name' => $name,
                        'is_link' => (($field['type'] ?? '') === 'multipleRecordLinks'),
                    ];
                }
            }
        }

        return $this->appointmentDoctorFieldCache = null;
    }

    /**
     * Lien direct Patients → Doctors (si la colonne existe dans Airtable).
     *
     * @return array{name: string, is_link: bool}|null
     */
    public function patientDoctorField(): ?array
    {
        if ($this->patientDoctorFieldCache !== null) {
            return $this->patientDoctorFieldCache;
        }

        $fromEnv = trim((string) config('services.airtable.fields.patient_doctor', ''));
        if ($fromEnv !== '') {
            return $this->patientDoctorFieldCache = ['name' => $fromEnv, 'is_link' => true];
        }

        $patientsTableName = (string) config('services.airtable.tables.patients', 'Patients');
        $usersTableName = (string) config('services.airtable.tables.users', 'Doctors');
        $usersTableId = null;
        $patientsTable = null;

        foreach ($this->baseTables() as $t) {
            if (($t['name'] ?? '') === $usersTableName) {
                $usersTableId = $t['id'] ?? null;
            }
            if (($t['name'] ?? '') === $patientsTableName) {
                $patientsTable = $t;
            }
        }

        if ($patientsTable === null || $usersTableId === null) {
            return $this->patientDoctorFieldCache = null;
        }

        foreach ($patientsTable['fields'] ?? [] as $field) {
            if (($field['type'] ?? '') !== 'multipleRecordLinks') {
                continue;
            }
            $linked = data_get($field, 'options.linkedTableId') ?? data_get($field, 'options.LinkedTableId');
            if ($linked === $usersTableId) {
                return $this->patientDoctorFieldCache = [
                    'name' => (string) ($field['name'] ?? 'doctor_id'),
                    'is_link' => true,
                ];
            }
        }

        return $this->patientDoctorFieldCache = null;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    public function appointmentBelongsToDoctor(array $record, ?string $doctorRecordId = null): bool
    {
        if ($this->shouldBypassIsolation()) {
            return true;
        }

        $doctorId = $doctorRecordId ?? $this->currentDoctorRecordId();
        if ($doctorId === null) {
            return false;
        }

        $field = $this->appointmentDoctorField();
        if ($field === null) {
            return false;
        }

        $raw = data_get($record, 'fields.'.$field['name']);
        if ($field['is_link']) {
            return is_array($raw) && in_array($doctorId, $raw, true);
        }

        return is_string($raw) && trim($raw) === $doctorId;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    public function patientBelongsToDoctor(array $record, ?string $doctorRecordId = null): bool
    {
        if ($this->shouldBypassIsolation()) {
            return true;
        }

        $doctorId = $doctorRecordId ?? $this->currentDoctorRecordId();
        if ($doctorId === null) {
            return false;
        }

        $direct = $this->patientDoctorField();
        if ($direct !== null) {
            $raw = data_get($record, 'fields.'.$direct['name']);
            if ($direct['is_link']) {
                return is_array($raw) && in_array($doctorId, $raw, true);
            }

            return is_string($raw) && trim($raw) === $doctorId;
        }

        $patientRecordId = (string) ($record['id'] ?? '');

        return $patientRecordId !== '' && isset($this->patientIdsForCurrentDoctor()[$patientRecordId]);
    }

    /**
     * IDs Airtable (rec…) des patients ayant au moins un RDV avec ce médecin.
     *
     * @return array<string, true>
     */
    public function patientIdsForCurrentDoctor(): array
    {
        if ($this->patientIdsForCurrentDoctorCache !== null) {
            return $this->patientIdsForCurrentDoctorCache;
        }

        if ($this->shouldBypassIsolation()) {
            return $this->patientIdsForCurrentDoctorCache = [];
        }

        $doctorId = $this->currentDoctorRecordId();
        if ($doctorId === null) {
            return $this->patientIdsForCurrentDoctorCache = [];
        }

        $doctorField = $this->appointmentDoctorField();
        $patientField = $this->appointmentPatientField();
        if ($doctorField === null || $patientField === null) {
            return $this->patientIdsForCurrentDoctorCache = [];
        }

        $set = [];
        foreach ($this->client->listAllRecordRows(AirtableClient::TABLE_APPOINTMENTS) as $row) {
            if (! $this->appointmentBelongsToDoctor($row, $doctorId)) {
                continue;
            }
            $raw = data_get($row, 'fields.'.$patientField['name']);
            if ($patientField['is_link']) {
                if (is_array($raw)) {
                    foreach ($raw as $pid) {
                        if (is_string($pid) && $pid !== '') {
                            $set[$pid] = true;
                        }
                    }
                }
            } elseif (is_string($raw) && str_starts_with($raw, 'rec')) {
                $set[$raw] = true;
            }
        }

        return $this->patientIdsForCurrentDoctorCache = $set;
    }

    /**
     * @return array{name: string, is_link: bool}|null
     */
    protected function appointmentPatientField(): ?array
    {
        $fromEnv = trim((string) config('services.airtable.fields.appointment_patient_link', ''));
        if ($fromEnv !== '') {
            return ['name' => $fromEnv, 'is_link' => true];
        }

        return ['name' => 'patient_id', 'is_link' => true];
    }
}
