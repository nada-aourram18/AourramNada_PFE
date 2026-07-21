<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Repositories\AppointmentRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function __construct(
        protected AppointmentRepository $appointments,
    ) {}

    public function index(): View
    {
        $all = $this->appointments->forCalendar(null, null, '');
        $initialDate = now()->toDateString();

        if ($all->isNotEmpty()) {
            // Open the month that has the most appointments so the grid is visibly filled.
            $month = $all
                ->groupBy(fn (Appointment $a) => $a->appointment_date->format('Y-m'))
                ->sortByDesc(fn ($group) => $group->count())
                ->keys()
                ->first();

            if (is_string($month) && $month !== '') {
                $initialDate = $month.'-01';
            }
        }

        return view('calendar.index', [
            'initialDate' => $initialDate,
            'appointmentCount' => $all->count(),
        ]);
    }

    public function events(Request $request): JsonResponse
    {
        $start = $request->date('start');
        $end = $request->date('end');
        $q = trim((string) $request->string('q'));

        $list = $this->appointments->forCalendar($start, $end, $q);

        $events = $list->values()->map(function (Appointment $a) {
            $color = match ($a->status) {
                'confirme' => '#0d9488',
                'en_attente' => '#F59E0B',
                default => '#EF4444',
            };

            $startAt = $a->startsAt()->toIso8601String();
            $endAt = $a->startsAt()->copy()->addHour()->toIso8601String();

            return [
                'id' => $a->id,
                'title' => ($a->patient?->full_name ?? __('messages.unknown_patient')).' — '.$a->consultation_type,
                'start' => $startAt,
                'end' => $endAt,
                'backgroundColor' => $color,
                'borderColor' => $color,
                'extendedProps' => [
                    'patient' => $a->patient?->full_name,
                    'phone' => $a->patient?->phone,
                    'type' => $a->consultation_type,
                    'status' => $a->status,
                    'uid' => $a->appointment_uid,
                    'google_calendar_event_id' => $a->google_calendar_event_id,
                ],
            ];
        })->values()->all();

        return response()->json($events);
    }
}
