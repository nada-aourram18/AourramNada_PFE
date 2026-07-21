<?php

namespace App\Models;

use App\Models\Concerns\RoutesAsAirtableRecord;
use App\Repositories\AppointmentRepository;
use Carbon\Carbon;
use Illuminate\Contracts\Routing\UrlRoutable;

class Appointment implements UrlRoutable
{
    use RoutesAsAirtableRecord;

    protected static function routeBindingRepository(): string
    {
        return AppointmentRepository::class;
    }

    public function __construct(
        public string $id,
        public string $appointment_uid,
        public string $patient_id,
        public Carbon $appointment_date,
        public string $appointment_time,
        public string $consultation_type,
        public string $status,
        public ?string $google_calendar_event_id,
        public ?Patient $patient = null,
    ) {}

    public function startsAt(): Carbon
    {
        $time = (string) $this->appointment_time;
        if (strlen($time) >= 5) {
            $time = substr($time, 0, 5);
        }

        return Carbon::parse($this->appointment_date->format('Y-m-d').' '.$time);
    }
}
