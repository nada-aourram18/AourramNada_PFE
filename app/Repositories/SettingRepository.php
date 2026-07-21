<?php

namespace App\Repositories;

use App\Services\AirtableClient;
use Illuminate\Support\Facades\Crypt;

class SettingRepository
{
    /** @var array<int, string> */
    protected static array $encryptedKeys = [
        'openai_api_key',
        'airtable_api_key',
    ];

    public function __construct(protected AirtableClient $client) {}

    protected function isConfigured(): bool
    {
        $name = trim((string) config('services.airtable.tables.settings', ''));

        return $name !== '';
    }

    public function get(string $key, ?string $default = null): ?string
    {
        if (! $this->isConfigured()) {
            return $default;
        }

        foreach ($this->allRows() as $row) {
            $fields = $row['fields'] ?? [];
            if (($fields['key'] ?? null) === $key) {
                $value = $fields['value'] ?? null;
                if ($value === null || $value === '') {
                    return $default;
                }
                if (in_array($key, self::$encryptedKeys, true)) {
                    try {
                        return Crypt::decryptString((string) $value);
                    } catch (\Throwable) {
                        return is_string($value) ? $value : $default;
                    }
                }

                return is_string($value) ? $value : $default;
            }
        }

        return $default;
    }

    public function set(string $key, ?string $value): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        if ($value === null || $value === '') {
            $this->deleteByKey($key);

            return;
        }

        $stored = in_array($key, self::$encryptedKeys, true)
            ? Crypt::encryptString($value)
            : $value;

        foreach ($this->allRows() as $row) {
            $fields = $row['fields'] ?? [];
            if (($fields['key'] ?? null) === $key) {
                $this->client->updateRecord(AirtableClient::TABLE_SETTINGS, $row['id'], [
                    'key' => $key,
                    'value' => $stored,
                ]);

                return;
            }
        }

        $this->client->createRecords(AirtableClient::TABLE_SETTINGS, [
            ['fields' => ['key' => $key, 'value' => $stored]],
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function allRows(): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        return $this->client->listAllRecordRows(AirtableClient::TABLE_SETTINGS);
    }

    protected function deleteByKey(string $key): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        foreach ($this->allRows() as $row) {
            $fields = $row['fields'] ?? [];
            if (($fields['key'] ?? null) === $key) {
                $this->client->deleteRecords(AirtableClient::TABLE_SETTINGS, [$row['id']]);

                return;
            }
        }
    }
}
