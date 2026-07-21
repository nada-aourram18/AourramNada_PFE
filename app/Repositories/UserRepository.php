<?php

namespace App\Repositories;

use App\Models\User;
use App\Services\AirtableClient;
use App\Support\AirtableFieldMap;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserRepository
{
    /** @var array<int, array<string, mixed>>|null */
    protected ?array $baseTablesCache = null;

    public function __construct(protected AirtableClient $client) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function baseTables(): array
    {
        return $this->baseTablesCache ??= $this->client->fetchBaseTables();
    }

    /**
     * @return array<string, true>
     */
    protected function usersTableFieldNameSet(): array
    {
        $usersTableName = (string) config('services.airtable.tables.users', 'Doctors');
        foreach ($this->baseTables() as $t) {
            if (($t['name'] ?? '') !== $usersTableName) {
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

    public function usersTableHasField(string $airtableColumnName): bool
    {
        $col = trim($airtableColumnName);
        if ($col === '') {
            return false;
        }
        $allowed = $this->usersTableFieldNameSet();

        return $allowed === [] || isset($allowed[$col]);
    }

    public function passwordColumnExists(): bool
    {
        return $this->usersTableHasField(AirtableFieldMap::userColumn('password'));
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    protected function filterFieldsForUsersTable(array $fields): array
    {
        $allowed = $this->usersTableFieldNameSet();
        if ($allowed === []) {
            return $fields;
        }

        return array_filter(
            $fields,
            fn ($_, string $key) => isset($allowed[$key]),
            ARRAY_FILTER_USE_BOTH
        );
    }

    protected function ensurePasswordColumnForWrite(): void
    {
        if (! $this->shouldSyncUserField('password')) {
            return;
        }
        if ($this->passwordColumnExists()) {
            return;
        }

        $table = (string) config('services.airtable.tables.users', 'Doctors');
        $col = AirtableFieldMap::userColumn('password');

        throw ValidationException::withMessages([
            'password' => [
                "Ajoutez la colonne « {$col} » (texte long) dans la table Airtable « {$table} », puis réessayez.",
            ],
        ]);
    }

    /** Nom du champ Airtable pour le rôle, ou null si absent de la base. */
    protected function airtableRoleField(): ?string
    {
        $name = (string) config('services.airtable.fields.user_role', '');

        return $name !== '' ? $name : null;
    }

    /**
     * @return array<int, string>
     */
    protected function adminEmails(): array
    {
        return config('services.airtable.admin_emails', ['admin@clinic.com']);
    }

    protected function inferRoleFromEmail(string $email): string
    {
        $e = Str::lower(trim($email));
        foreach ($this->adminEmails() as $admin) {
            if (Str::lower(trim($admin)) === $e) {
                return 'admin';
            }
        }

        return 'receptionist';
    }

    /**
     * @return list<string>
     */
    protected function userSyncFieldNames(): array
    {
        $list = config('services.airtable.user_sync_fields', ['name', 'email', 'password']);

        return $list !== [] ? $list : ['name', 'email', 'password'];
    }

    protected function shouldSyncUserField(string $logicalName): bool
    {
        return in_array($logicalName, $this->userSyncFieldNames(), true);
    }

    public function find(string $id): ?User
    {
        try {
            $rec = $this->client->getRecord(AirtableClient::TABLE_USERS, $id);

            return $this->mapFromRecord($rec);
        } catch (\Throwable) {
            return null;
        }
    }

    public function findOrFail(string $id): User
    {
        $u = $this->find($id);
        if ($u === null) {
            throw (new ModelNotFoundException)->setModel(User::class, [$id]);
        }

        return $u;
    }

    public function findByEmail(string $email): ?User
    {
        $email = Str::lower(trim($email));
        if ($email === '') {
            return null;
        }

        $col = AirtableFieldMap::userColumn('email');
        // Airtable string escape: double single quotes.
        $escaped = str_replace("'", "''", $email);
        $formula = sprintf("LOWER({%s})='%s'", $col, $escaped);

        $resp = $this->client->listRecords(AirtableClient::TABLE_USERS, [
            'pageSize' => 1,
            'filterByFormula' => $formula,
        ]);

        $row = $resp['records'][0] ?? null;
        if (! is_array($row)) {
            return null;
        }

        return $this->mapFromRecord($row);
    }

    public function emailExists(string $email, ?string $exceptUserId = null): bool
    {
        $user = $this->findByEmail($email);
        if ($user === null) {
            return false;
        }
        if ($exceptUserId !== null && $user->id === $exceptUserId) {
            return false;
        }

        return true;
    }

    /**
     * @return Collection<int, User>
     */
    public function receptionists(): Collection
    {
        $rows = $this->client->listAllRecordRows(AirtableClient::TABLE_USERS);
        $users = collect($rows)
            ->map(fn (array $r) => $this->mapFromRecord($r))
            ->filter(function (User $u) {
                if ($this->airtableRoleField() !== null) {
                    return $u->role === 'receptionist';
                }

                return ! $u->isAdmin();
            })
            ->sortBy(fn (User $u) => Str::lower($u->name))
            ->values();

        return $users;
    }

    /**
     * @param  array{name?: string, email?: string, password?: string, role?: string, phone?: string|null, clinic_name?: string|null}  $data
     */
    public function create(array $data): User
    {
        $this->ensurePasswordColumnForWrite();

        $plain = (string) ($data['password'] ?? '');
        $logical = [];
        if ($this->shouldSyncUserField('name')) {
            $logical['name'] = (string) ($data['name'] ?? '');
        }
        if ($this->shouldSyncUserField('email')) {
            $logical['email'] = Str::lower((string) ($data['email'] ?? ''));
        }
        if ($this->shouldSyncUserField('password')) {
            $logical['password'] = Hash::make($plain);
        }
        if ($this->shouldSyncUserField('phone') && array_key_exists('phone', $data)) {
            $logical['phone'] = (string) $data['phone'];
        }
        if ($this->shouldSyncUserField('clinic_name') && array_key_exists('clinic_name', $data)) {
            $logical['clinic_name'] = (string) $data['clinic_name'];
        }
        if ($this->shouldSyncUserField('specialty') && array_key_exists('specialty', $data)) {
            $logical['specialty'] = (string) $data['specialty'];
        }
        if ($this->shouldSyncUserField('avatar_path') && ! empty($data['avatar_path'])) {
            $logical['avatar_path'] = (string) $data['avatar_path'];
        }
        if ($this->shouldSyncUserField('theme') && ! empty($data['theme'])) {
            $logical['theme'] = (string) $data['theme'];
        }

        $fields = AirtableFieldMap::mapUserFieldsToAirtable($logical);
        $roleField = $this->airtableRoleField();
        if ($roleField !== null && $this->usersTableHasField($roleField)) {
            $fields[$roleField] = (string) ($data['role'] ?? 'receptionist');
        }

        $fields = $this->filterFieldsForUsersTable($fields);

        $resp = $this->client->createRecords(AirtableClient::TABLE_USERS, [
            ['fields' => $fields],
        ]);
        $records = $resp['records'] ?? [];
        if ($records === []) {
            throw new \RuntimeException('Airtable: création utilisateur impossible.');
        }

        return $this->mapFromRecord($records[0]);
    }

    public function update(User $user): void
    {
        $this->normalizePasswordForStorage($user);
        $logical = [];
        if ($this->shouldSyncUserField('name')) {
            $logical['name'] = $user->name;
        }
        if ($this->shouldSyncUserField('email')) {
            $logical['email'] = Str::lower($user->email);
        }
        if ($this->shouldSyncUserField('password')) {
            $logical['password'] = $user->password;
        }
        if ($this->shouldSyncUserField('phone')) {
            $logical['phone'] = $user->phone;
        }
        if ($this->shouldSyncUserField('clinic_name')) {
            $logical['clinic_name'] = $user->clinic_name;
        }
        if ($this->shouldSyncUserField('specialty')) {
            $logical['specialty'] = $user->specialty;
        }
        $fields = AirtableFieldMap::mapUserFieldsToAirtable($logical);
        if ($this->shouldSyncUserField('avatar_path')) {
            $fields['avatar_path'] = $user->avatar_path;
        }
        if ($this->shouldSyncUserField('theme')) {
            $fields['theme'] = $user->theme;
        }
        $roleField = $this->airtableRoleField();
        if ($roleField !== null) {
            $fields[$roleField] = $user->role;
        }
        if ($this->shouldSyncUserField('remember_token') && $user->remember_token !== null) {
            $fields['remember_token'] = $user->remember_token;
        }
        $fields = $this->filterFieldsForUsersTable(array_filter(
            $fields,
            fn ($v) => $v !== null
        ));
        $this->client->updateRecord(AirtableClient::TABLE_USERS, $user->id, $fields);
    }

    /**
     * @param  array{name?: string, email?: string, password?: string, role?: string, phone?: string|null, clinic_name?: string|null}  $data
     */
    public function updateOrCreateByEmail(string $email, array $data): User
    {
        $existing = $this->findByEmail($email);
        if ($existing !== null) {
            foreach ($data as $k => $v) {
                if ($k === 'password' && is_string($v)) {
                    $existing->password = $v;
                } elseif (property_exists($existing, $k)) {
                    $existing->{$k} = $v;
                }
            }
            $this->update($existing);

            return $this->findOrFail($existing->id);
        }

        $data['email'] = $email;

        return $this->create($data);
    }

    public function delete(string $id): void
    {
        $this->client->deleteRecords(AirtableClient::TABLE_USERS, [$id]);
    }

    /**
     * @param  array<string, mixed>  $record
     */
    public function mapFromRecord(array $record): User
    {
        $f = $record['fields'] ?? [];
        $user = new User;
        $user->id = $record['id'];
        $user->name = (string) (AirtableFieldMap::userValue($f, 'name') ?? '');
        $user->email = Str::lower((string) (AirtableFieldMap::userValue($f, 'email') ?? ''));
        $user->password = (string) (AirtableFieldMap::userValue($f, 'password') ?? '');
        $roleField = $this->airtableRoleField();
        if ($roleField !== null && isset($f[$roleField])) {
            $user->role = (string) $f[$roleField];
        } elseif ($roleField !== null && isset($f['role'])) {
            $user->role = (string) $f['role'];
        } else {
            $user->role = $this->inferRoleFromEmail($user->email);
        }
        $phone = AirtableFieldMap::userValue($f, 'phone');
        $user->phone = $phone !== null && $phone !== '' ? (string) $phone : null;
        $clinic = AirtableFieldMap::userValue($f, 'clinic_name');
        $user->clinic_name = $clinic !== null && $clinic !== '' ? (string) $clinic : null;
        $specialty = AirtableFieldMap::userValue($f, 'specialty');
        $user->specialty = $specialty !== null && $specialty !== '' ? (string) $specialty : null;
        $user->avatar_path = isset($f['avatar_path']) ? (string) $f['avatar_path'] : null;
        $user->theme = (string) ($f['theme'] ?? 'light');
        $user->remember_token = isset($f['remember_token']) ? (string) $f['remember_token'] : null;

        return $user;
    }

    protected function normalizePasswordForStorage(User $user): void
    {
        if ($user->password !== '' && ! str_starts_with($user->password, '$2y$')) {
            $user->password = Hash::make($user->password);
        }
    }
}
