<?php

namespace App\Models;

use App\Models\Concerns\RoutesAsAirtableRecord;
use App\Repositories\ConversationRepository;
use Carbon\Carbon;
use Illuminate\Contracts\Routing\UrlRoutable;

class Conversation implements UrlRoutable
{
    use RoutesAsAirtableRecord;

    protected static function routeBindingRepository(): string
    {
        return ConversationRepository::class;
    }

    /**
     * @param  array<int, array<string, mixed>>  $messages
     */
    public function __construct(
        public string $id,
        public ?string $patient_id,
        public string $language,
        public array $messages,
        public string $status,
        public ?Patient $patient = null,
        public ?Carbon $created_at = null,
    ) {}

    public function messagesCount(): int
    {
        return count($this->messages);
    }
}
