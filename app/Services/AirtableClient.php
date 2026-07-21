<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class AirtableClient
{
    /** @see config('services.airtable.tables') */
    public const TABLE_APPOINTMENTS = 'appointments';

    public const TABLE_PATIENTS = 'patients';

    public const TABLE_SETTINGS = 'settings';

    public const TABLE_USERS = 'users';

    public const TABLE_CONVERSATIONS = 'conversations';

    public function __construct(
        protected string $apiKey,
        protected string $baseId,
        /** @var array<string, string> logical key => nom exact de la table dans Airtable */
        protected array $tables,
        protected bool $httpVerifySsl = true,
    ) {}

    /** @var list<array<string, mixed>>|null */
    private ?array $metaTablesCache = null;

    /**
     * Schéma des tables (API meta). Nécessite le scope schema.bases:read sur le jeton.
     *
     * @return list<array<string, mixed>>
     */
    public function fetchBaseTables(): array
    {
        if ($this->metaTablesCache !== null) {
            return $this->metaTablesCache;
        }
        $url = 'https://api.airtable.com/v0/meta/bases/'.$this->baseId.'/tables';
        $response = $this->http()->get($url);
        if (! $response->successful()) {
            return $this->metaTablesCache = [];
        }

        return $this->metaTablesCache = $response->json('tables') ?? [];
    }

    public static function fromConfig(): self
    {
        $config = config('services.airtable', []);

        return new self(
            (string) ($config['api_key'] ?? ''),
            (string) ($config['base_id'] ?? ''),
            (array) ($config['tables'] ?? []),
            (bool) ($config['http_verify'] ?? true),
        );
    }

    /**
     * @param  self::TABLE_*  $logicalKey
     */
    public function tableName(string $logicalKey): string
    {
        $name = $this->tables[$logicalKey] ?? null;
        if ($name === null || $name === '') {
            throw new \InvalidArgumentException("Table Airtable inconnue ou vide : {$logicalKey}");
        }

        return $name;
    }

    /**
     * @param  self::TABLE_*  $logicalKey
     */
    protected function endpoint(string $logicalKey, ?string $recordId = null): string
    {
        $table = rawurlencode($this->tableName($logicalKey));
        $path = "https://api.airtable.com/v0/{$this->baseId}/{$table}";
        if ($recordId !== null) {
            $path .= '/'.rawurlencode($recordId);
        }

        return $path;
    }

    protected function http()
    {
        $req = Http::withToken($this->apiKey)
            ->acceptJson()
            ->asJson()
            // Avoid PHP max_execution_time (30s) when Airtable is slow / unreachable.
            ->connectTimeout(5)
            ->timeout(12);

        if (! $this->httpVerifySsl) {
            $req = $req->withOptions(['verify' => false]);
        }

        return $req;
    }

    /**
     * @param  self::TABLE_*  $logicalKey
     * @param  array<string, mixed>  $query  ex. pageSize, offset, filterByFormula, sort, fields, view
     * @return array<string, mixed>
     *
     * @throws RequestException
     */
    public function listRecords(string $logicalKey, array $query = []): array
    {
        $response = $this->http()->get($this->endpoint($logicalKey), $query);
        $response->throw();

        return $response->json();
    }

    /**
     * Parcourt toutes les pages Airtable (offset) et retourne la liste des enregistrements bruts.
     *
     * @param  self::TABLE_*  $logicalKey
     * @return array<int, array<string, mixed>>
     */
    public function listAllRecordRows(string $logicalKey, array $baseQuery = []): array
    {
        $all = [];
        $offset = null;

        do {
            $query = array_merge($baseQuery, ['pageSize' => 100]);
            if ($offset !== null) {
                $query['offset'] = $offset;
            }
            $resp = $this->listRecords($logicalKey, $query);
            $records = $resp['records'] ?? [];
            foreach ($records as $rec) {
                $all[] = $rec;
            }
            $offset = $resp['offset'] ?? null;
        } while ($offset !== null);

        return $all;
    }

    /**
     * @param  self::TABLE_*  $logicalKey
     * @param  array<int, array<string, mixed>>  $records  [['fields' => [...]], ...] max 10 par requête (limite Airtable)
     * @return array<string, mixed>
     */
    public function createRecords(string $logicalKey, array $records): array
    {
        $created = [];
        foreach (array_chunk($records, 10) as $chunk) {
            $response = $this->http()->post($this->endpoint($logicalKey), [
                'records' => $chunk,
            ]);
            $response->throw();
            $json = $response->json();
            foreach ($json['records'] ?? [] as $rec) {
                $created[] = $rec;
            }
        }

        return ['records' => $created];
    }

    /**
     * @param  self::TABLE_*  $logicalKey
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    public function updateRecord(string $logicalKey, string $recordId, array $fields, bool $typecast = false): array
    {
        $body = ['fields' => $fields];
        if ($typecast) {
            $body['typecast'] = true;
        }
        $response = $this->http()->patch($this->endpoint($logicalKey, $recordId), $body);
        $response->throw();

        return $response->json();
    }

    /**
     * @param  self::TABLE_*  $logicalKey
     * @return array<string, mixed>
     */
    public function getRecord(string $logicalKey, string $recordId): array
    {
        $response = $this->http()->get($this->endpoint($logicalKey, $recordId));
        $response->throw();

        return $response->json();
    }

    /**
     * @param  self::TABLE_*  $logicalKey
     * @param  array<int, string>  $recordIds
     * @return array<string, mixed>
     */
    public function deleteRecords(string $logicalKey, array $recordIds): array
    {
        $ids = array_values(array_filter($recordIds));
        // Airtable attend plusieurs clés records[] — construire l’URL à la main
        $parts = [];
        foreach ($ids as $id) {
            $parts[] = 'records[]='.rawurlencode($id);
        }
        $url = $this->endpoint($logicalKey).'?'.implode('&', $parts);

        $response = $this->http()->delete($url);
        $response->throw();

        return $response->json();
    }
}
