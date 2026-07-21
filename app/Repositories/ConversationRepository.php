<?php

namespace App\Repositories;

use App\Models\Conversation;
use App\Services\AirtableClient;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ConversationRepository
{
    public function __construct(
        protected AirtableClient $client,
        protected PatientRepository $patients,
    ) {}

    /**
     * Si false : aucune table « Conversations » dans Airtable (compteur = 0, pas d’API).
     */
    public function usesAirtable(): bool
    {
        return filled(config('services.airtable.tables.conversations'));
    }

    public function find(string $id): ?Conversation
    {
        if (! $this->usesAirtable()) {
            return null;
        }

        try {
            $rec = $this->client->getRecord(AirtableClient::TABLE_CONVERSATIONS, $id);

            return $this->mapFromRecord($rec, true);
        } catch (\Throwable) {
            return null;
        }
    }

    public function findOrFail(string $id): Conversation
    {
        if (! $this->usesAirtable()) {
            throw (new ModelNotFoundException)->setModel(Conversation::class, [$id]);
        }

        $c = $this->find($id);
        if ($c === null) {
            throw (new ModelNotFoundException)->setModel(Conversation::class, [$id]);
        }

        return $c;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    public function mapFromRecord(array $record, bool $withPatient = false): Conversation
    {
        $f = $record['fields'] ?? [];
        $link = $f['patient_id'] ?? [];
        $patientId = is_array($link) && $link !== [] ? (string) $link[0] : null;

        $raw = $f['messages'] ?? '[]';
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $messages = is_array($decoded) ? $decoded : [];
        } elseif (is_array($raw)) {
            $messages = $raw;
        } else {
            $messages = [];
        }

        $patient = null;
        if ($withPatient && $patientId !== null && $patientId !== '') {
            $patient = $this->patients->find($patientId);
        }

        $created = isset($record['createdTime']) ? Carbon::parse((string) $record['createdTime']) : null;

        return new Conversation(
            id: $record['id'],
            patient_id: $patientId,
            language: (string) ($f['language'] ?? 'fr'),
            messages: $messages,
            status: (string) ($f['status'] ?? 'active'),
            patient: $patient,
            created_at: $created,
        );
    }

    /**
     * @return Collection<int, Conversation>
     */
    public function allWithPatients(): Collection
    {
        if (! $this->usesAirtable()) {
            return collect();
        }

        $rows = $this->client->listAllRecordRows(AirtableClient::TABLE_CONVERSATIONS);

        return collect($rows)->map(fn (array $r) => $this->mapFromRecord($r, true));
    }

    /**
     * @return LengthAwarePaginator<int, Conversation>
     */
    public function paginateIndex(int $page, int $perPage, ?string $language, ?string $status): LengthAwarePaginator
    {
        $items = $this->allWithPatients()->filter(function (Conversation $c) use ($language, $status) {
            if ($language !== null && $language !== '' && $c->language !== $language) {
                return false;
            }
            if ($status !== null && $status !== '' && $c->status !== $status) {
                return false;
            }

            return true;
        })->sortByDesc(fn (Conversation $c) => $c->created_at?->timestamp ?? 0)->values();

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

    public function countActive(): int
    {
        if (! $this->usesAirtable()) {
            return 0;
        }

        return $this->allWithPatients()->filter(fn (Conversation $c) => $c->status === 'active')->count();
    }

    public function create(array $data): Conversation
    {
        if (! $this->usesAirtable()) {
            throw new \RuntimeException('Table Conversations Airtable non configurée.');
        }

        $messages = $data['messages'] ?? [];
        $fields = [
            'language' => (string) ($data['language'] ?? 'fr'),
            'messages' => json_encode($messages, JSON_UNESCAPED_UNICODE),
            'status' => (string) ($data['status'] ?? 'active'),
        ];
        if (! empty($data['patient_id'])) {
            $fields['patient_id'] = [(string) $data['patient_id']];
        }

        $resp = $this->client->createRecords(AirtableClient::TABLE_CONVERSATIONS, [
            ['fields' => $fields],
        ]);
        $records = $resp['records'] ?? [];
        if ($records === []) {
            throw new \RuntimeException('Airtable: création conversation impossible.');
        }

        return $this->mapFromRecord($records[0], true);
    }

    public function update(Conversation $conversation, array $data): Conversation
    {
        if (! $this->usesAirtable()) {
            return $conversation;
        }

        $fields = [];
        if (array_key_exists('messages', $data)) {
            $fields['messages'] = json_encode($data['messages'], JSON_UNESCAPED_UNICODE);
        }
        if (array_key_exists('language', $data)) {
            $fields['language'] = (string) $data['language'];
        }
        if (array_key_exists('status', $data)) {
            $fields['status'] = (string) $data['status'];
        }
        if (array_key_exists('patient_id', $data)) {
            $pid = $data['patient_id'];
            $fields['patient_id'] = $pid ? [(string) $pid] : [];
        }

        if ($fields === []) {
            return $conversation;
        }

        $rec = $this->client->updateRecord(AirtableClient::TABLE_CONVERSATIONS, $conversation->id, $fields);

        return $this->mapFromRecord($rec, true);
    }
}
