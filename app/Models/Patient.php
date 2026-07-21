<?php

namespace App\Models;

use App\Models\Concerns\RoutesAsAirtableRecord;
use App\Repositories\PatientRepository;
use Carbon\Carbon;
use Illuminate\Contracts\Routing\UrlRoutable;

class Patient implements UrlRoutable
{
    use RoutesAsAirtableRecord;

    protected static function routeBindingRepository(): string
    {
        return PatientRepository::class;
    }

    public function __construct(
        public string $id,
        public string $patient_uid,
        public string $full_name,
        public string $phone,
        public string $language,
        public ?string $notes,
        public ?Carbon $created_at = null,
        public ?string $email = null,
    ) {}
}
