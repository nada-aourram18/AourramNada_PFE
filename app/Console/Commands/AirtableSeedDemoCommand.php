<?php

namespace App\Console\Commands;

use App\Repositories\AppointmentRepository;
use App\Repositories\PatientRepository;
use App\Repositories\UserRepository;
use App\Services\AirtableClient;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AirtableSeedDemoCommand extends Command
{
    /** @var array<string, true> */
    protected array $missingOwnerFieldWarned = [];

    protected $signature = 'airtable:seed-demo {--force : Exécuter sans demander confirmation}';

    protected $description = 'Crée des patients, rendez-vous et un compte admin de test dans Airtable (via l’API).';

    public function handle(
        PatientRepository $patients,
        AppointmentRepository $appointments,
        UserRepository $users,
        AirtableClient $client,
    ): int {
        if (empty(config('services.airtable.api_key')) || empty(config('services.airtable.base_id'))) {
            $this->error('Renseigne AIRTABLE_API_KEY et AIRTABLE_BASE_ID dans .env puis : php artisan config:clear');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Créer des enregistrements de démo dans cette base Airtable ?', true)) {
            $this->info('Annulé.');

            return self::SUCCESS;
        }

        $this->info('Création en cours…');

        try {
            $admin = $users->updateOrCreateByEmail('admin@clinic.com', [
                'name' => 'Administrateur démo',
                'password' => 'password',
                'role' => 'admin',
            ]);
            $this->fillDemoUserDetails($client, $admin->id);
            $this->line('Utilisateur admin : admin@clinic.com / password — '.$admin->id);

            $nada = $users->updateOrCreateByEmail('nadaaourram6@gmail.com', [
                'name' => 'Nada Yak',
                'password' => 'password',
                'phone' => '+212612345678',
                'clinic_name' => 'Cabinet Nada',
                'theme' => 'light',
            ]);
            $this->line('Utilisateur test : nadaaourram6@gmail.com / password — '.$nada->id);

            $nouveau = $users->updateOrCreateByEmail('nouveau@clinic-demo.com', [
                'name' => 'Réceptionniste Démonstration',
                'password' => 'password',
                'phone' => '+212600888777',
                'clinic_name' => 'Cabinet Démonstration',
                'theme' => 'light',
            ]);
            $this->line('Nouvel utilisateur : nouveau@clinic-demo.com / password — '.$nouveau->id);

            $p1 = $patients->create([
                'full_name' => 'Fatima Alami',
                'phone' => '+212600000001',
                'language' => 'fr',
                'notes' => 'Patient démo — hypertension',
            ]);
            $p2 = $patients->create([
                'full_name' => 'Omar Benali',
                'phone' => '+212600000002',
                'language' => 'ar',
                'notes' => 'Patient démo — contrôle dentaire',
            ]);
            $this->attachDoctorIfPresent(
                $client,
                AirtableClient::TABLE_PATIENTS,
                [$p1->id, $p2->id],
                $admin->id
            );
            $this->line('Patients : '.$p1->patient_uid.' ('.$p1->full_name.'), '.$p2->patient_uid.' ('.$p2->full_name.')');

            $n1 = $patients->create([
                'full_name' => 'Youssef Karim',
                'phone' => '+212600111001',
                'language' => 'fr',
                'notes' => 'Patient test Nada',
            ]);
            $n2 = $patients->create([
                'full_name' => 'Salma Idrissi',
                'phone' => '+212600111002',
                'language' => 'ar',
                'notes' => 'Patient test Nada',
            ]);
            $this->attachDoctorIfPresent(
                $client,
                AirtableClient::TABLE_PATIENTS,
                [$n1->id, $n2->id],
                $nada->id
            );
            $this->line('Patients Nada : '.$n1->patient_uid.' ('.$n1->full_name.'), '.$n2->patient_uid.' ('.$n2->full_name.')');

            $j1 = $patients->create([
                'full_name' => 'Amine Tazi',
                'phone' => '+212600222001',
                'language' => 'fr',
                'notes' => 'Patient démo — nouveau compte',
            ]);
            $j2 = $patients->create([
                'full_name' => 'Khadija Mansouri',
                'phone' => '+212600222002',
                'language' => 'ar',
                'notes' => 'Patient démo — suivi général',
            ]);
            $this->attachDoctorIfPresent(
                $client,
                AirtableClient::TABLE_PATIENTS,
                [$j1->id, $j2->id],
                $nouveau->id
            );
            $this->line('Patients nouveau compte : '.$j1->patient_uid.' ('.$j1->full_name.'), '.$j2->patient_uid.' ('.$j2->full_name.')');

            $j3 = $patients->create([
                'full_name' => 'Rachid El Fassi',
                'phone' => '+212600222003',
                'language' => 'fr',
                'notes' => 'Sous-compte démo — suivi cardiologie',
            ]);
            $j4 = $patients->create([
                'full_name' => 'Hanane Bennis',
                'phone' => '+212600222004',
                'language' => 'fr',
                'notes' => 'Sous-compte démo — première visite',
            ]);
            $this->attachDoctorIfPresent(
                $client,
                AirtableClient::TABLE_PATIENTS,
                [$j3->id, $j4->id],
                $nouveau->id
            );
            $this->line('Patients sous-compte (suite) : '.$j3->patient_uid.' ('.$j3->full_name.'), '.$j4->patient_uid.' ('.$j4->full_name.')');

            $today = Carbon::today();
            $a1 = $appointments->create([
                'patient_id' => $p1->id,
                'appointment_date' => $today->copy()->addDay(),
                'appointment_time' => '10:30',
                'consultation_type' => 'general',
                'status' => 'confirme',
            ]);
            $a2 = $appointments->create([
                'patient_id' => $p2->id,
                'appointment_date' => $today,
                'appointment_time' => '14:00',
                'consultation_type' => 'dentaire',
                'status' => 'en_attente',
            ]);
            $a3 = $appointments->create([
                'patient_id' => $p1->id,
                'appointment_date' => $today->copy()->subDay(),
                'appointment_time' => '09:15',
                'consultation_type' => 'autre',
                'status' => 'annule',
            ]);
            $this->attachDoctorIfPresent(
                $client,
                AirtableClient::TABLE_APPOINTMENTS,
                [$a1->id, $a2->id, $a3->id],
                $admin->id
            );
            $this->line('Rendez-vous : '.$a1->appointment_uid.', '.$a2->appointment_uid.', '.$a3->appointment_uid);

            $nA1 = $appointments->create([
                'patient_id' => $n1->id,
                'appointment_date' => $today->copy()->addDay(),
                'appointment_time' => '10:00',
                'consultation_type' => 'general',
                'status' => 'en_attente',
            ]);
            $nA2 = $appointments->create([
                'patient_id' => $n2->id,
                'appointment_date' => $today->copy()->addDays(2),
                'appointment_time' => '14:30',
                'consultation_type' => 'dentaire',
                'status' => 'confirme',
            ]);
            $nA3 = $appointments->create([
                'patient_id' => $n1->id,
                'appointment_date' => $today->copy()->subDay(),
                'appointment_time' => '09:15',
                'consultation_type' => 'autre',
                'status' => 'annule',
            ]);
            $this->attachDoctorIfPresent(
                $client,
                AirtableClient::TABLE_APPOINTMENTS,
                [$nA1->id, $nA2->id, $nA3->id],
                $nada->id
            );
            $this->line('Rendez-vous Nada : '.$nA1->appointment_uid.', '.$nA2->appointment_uid.', '.$nA3->appointment_uid);

            $jA1 = $appointments->create([
                'patient_id' => $j1->id,
                'appointment_date' => $today->copy()->addDay(),
                'appointment_time' => '11:00',
                'consultation_type' => 'general',
                'status' => 'confirme',
            ]);
            $jA2 = $appointments->create([
                'patient_id' => $j2->id,
                'appointment_date' => $today->copy()->addDays(3),
                'appointment_time' => '15:00',
                'consultation_type' => 'dentaire',
                'status' => 'en_attente',
            ]);
            $jA3 = $appointments->create([
                'patient_id' => $j1->id,
                'appointment_date' => $today,
                'appointment_time' => '16:30',
                'consultation_type' => 'autre',
                'status' => 'en_attente',
            ]);
            $this->attachDoctorIfPresent(
                $client,
                AirtableClient::TABLE_APPOINTMENTS,
                [$jA1->id, $jA2->id, $jA3->id],
                $nouveau->id
            );
            $this->line('Rendez-vous nouveau compte : '.$jA1->appointment_uid.', '.$jA2->appointment_uid.', '.$jA3->appointment_uid);

            $jA4 = $appointments->create([
                'patient_id' => $j3->id,
                'appointment_date' => $today->copy()->addDays(4),
                'appointment_time' => '08:45',
                'consultation_type' => 'general',
                'status' => 'en_attente',
            ]);
            $jA5 = $appointments->create([
                'patient_id' => $j4->id,
                'appointment_date' => $today->copy()->addDays(5),
                'appointment_time' => '12:15',
                'consultation_type' => 'dentaire',
                'status' => 'confirme',
            ]);
            $jA6 = $appointments->create([
                'patient_id' => $j3->id,
                'appointment_date' => $today->copy()->addDay(),
                'appointment_time' => '17:00',
                'consultation_type' => 'general',
                'status' => 'confirme',
            ]);
            $this->attachDoctorIfPresent(
                $client,
                AirtableClient::TABLE_APPOINTMENTS,
                [$jA4->id, $jA5->id, $jA6->id],
                $nouveau->id
            );
            $this->line('Rendez-vous sous-compte (suite) : '.$jA4->appointment_uid.', '.$jA5->appointment_uid.', '.$jA6->appointment_uid);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Terminé. Connexions : admin@clinic.com / password ; nadaaourram6@gmail.com / password ; sous-compte nouveau@clinic-demo.com / password (4 patients, 6 RDV) — vérifie Patients & Rendez-vous dans l’app.');

        return self::SUCCESS;
    }

    protected function fillDemoUserDetails(AirtableClient $client, string $userRecordId): void
    {
        $candidate = [
            'phone' => '+212600000099',
            'clinic_name' => 'Cabinet Medical Demo',
            'theme' => 'dark',
            'remember_token' => null,
            'avatar_path' => null,
        ];
        $extra = [];
        $tables = $client->fetchBaseTables();
        $usersTableName = $client->tableName(AirtableClient::TABLE_USERS);
        $existingFields = [];
        foreach ($tables as $table) {
            if (($table['name'] ?? '') !== $usersTableName) {
                continue;
            }
            foreach (($table['fields'] ?? []) as $f) {
                $n = trim((string) ($f['name'] ?? ''));
                if ($n !== '') {
                    $existingFields[] = $n;
                }
            }
            break;
        }

        foreach ($candidate as $key => $value) {
            if (in_array($key, $existingFields, true) && $value !== null) {
                $extra[$key] = $value;
            }
        }

        if ($extra === []) {
            return;
        }

        $client->updateRecord(AirtableClient::TABLE_USERS, $userRecordId, $extra, true);
    }

    /**
     * Même logique que PatientRepository / AppointmentRepository : lien vers la table users
     * ou champs doctor, owner_user_id, user_id, owner, receptionist.
     *
     * @return array{name: string, is_link: bool}|null
     */
    protected function resolveUserOwnerFieldForTable(AirtableClient $client, string $logicalTable): ?array
    {
        $metaTables = $client->fetchBaseTables();
        $targetName = $client->tableName($logicalTable);
        $usersTableName = $client->tableName(AirtableClient::TABLE_USERS);

        $usersTableId = null;
        $targetTable = null;
        foreach ($metaTables as $table) {
            if (($table['name'] ?? '') === $usersTableName) {
                $usersTableId = $table['id'] ?? null;
            }
            if (($table['name'] ?? '') === $targetName) {
                $targetTable = $table;
            }
        }
        if ($targetTable === null) {
            return null;
        }

        $fields = $targetTable['fields'] ?? [];
        $candidates = ['doctor', 'owner_user_id', 'user_id', 'owner', 'receptionist'];

        if ($usersTableId !== null) {
            foreach ($fields as $field) {
                if (($field['type'] ?? '') !== 'multipleRecordLinks') {
                    continue;
                }
                $linked = data_get($field, 'options.linkedTableId') ?? data_get($field, 'options.LinkedTableId');
                if ($linked === $usersTableId) {
                    return [
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
                    return [
                        'name' => $name,
                        'is_link' => (($field['type'] ?? '') === 'multipleRecordLinks'),
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Renseigne le champ propriétaire (lien user) sur les lignes créées, comme en session web.
     *
     * @param  list<string>  $recordIds
     */
    protected function attachDoctorIfPresent(
        AirtableClient $client,
        string $logicalTable,
        array $recordIds,
        string $doctorUserRecordId
    ): void {
        if ($recordIds === [] || $doctorUserRecordId === '') {
            return;
        }

        $ownerField = $this->resolveUserOwnerFieldForTable($client, $logicalTable);
        if ($ownerField === null) {
            if (! isset($this->missingOwnerFieldWarned[$logicalTable])) {
                $this->missingOwnerFieldWarned[$logicalTable] = true;
                $this->warn('Table '.$client->tableName($logicalTable).' : aucun champ propriétaire détecté (lien vers « '.$client->tableName(AirtableClient::TABLE_USERS).' » ou doctor/owner/receptionist). Les données du seed ne seront pas rattachées à un utilisateur.');
            }

            return;
        }

        foreach ($recordIds as $recordId) {
            $client->updateRecord(
                $logicalTable,
                $recordId,
                [
                    $ownerField['name'] => $ownerField['is_link']
                        ? [$doctorUserRecordId]
                        : $doctorUserRecordId,
                ],
                true
            );
        }
    }
}
