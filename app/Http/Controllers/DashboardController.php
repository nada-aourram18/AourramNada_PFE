<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Repositories\AppointmentRepository;
use App\Repositories\ConversationRepository;
use App\Repositories\PatientRepository;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected AppointmentRepository $appointments,
        protected PatientRepository $patients,
        protected ConversationRepository $conversations,
    ) {}

    public function index(): View
    {
        $today = Carbon::today();

        $allAppointments = $this->appointments->allWithPatients();
        $allPatients = $this->patients->allAsCollection();

        $appointmentsToday = $allAppointments->filter(
            fn (Appointment $a) => $a->appointment_date->toDateString() === $today->toDateString()
        )->count();

        $patientsThisMonth = $allPatients->filter(function (Patient $p) use ($today) {
            if ($p->created_at === null) {
                return false;
            }

            return $p->created_at->between(
                $today->copy()->startOfMonth(),
                $today->copy()->endOfMonth()
            );
        })->count();

        $confirmed = $allAppointments->filter(fn (Appointment $a) => $a->status === 'confirme')->count();
        $pending = $allAppointments->filter(fn (Appointment $a) => $a->status === 'en_attente')->count();
        $cancelled = $allAppointments->filter(fn (Appointment $a) => $a->status === 'annule')->count();

        $activeConversations = $this->conversations->countActive();

        $chartLabels = [];
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = $today->copy()->subDays($i);
            $chartLabels[] = $day->translatedFormat('D j');
            $chartData[] = $allAppointments->filter(
                fn (Appointment $a) => $a->appointment_date->toDateString() === $day->toDateString()
            )->count();
        }

        $latestAppointments = $allAppointments
            ->sortByDesc(fn (Appointment $a) => $a->startsAt()->timestamp)
            ->take(5)
            ->values();

        return view('dashboard.index', compact(
            'appointmentsToday',
            'patientsThisMonth',
            'confirmed',
            'pending',
            'cancelled',
            'activeConversations',
            'chartLabels',
            'chartData',
            'latestAppointments'
        ));
    }
}
