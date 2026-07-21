<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppointmentRequest;
use App\Http\Requests\UpdateAppointmentStatusRequest;
use App\Models\Appointment;
use App\Repositories\AppointmentRepository;
use App\Repositories\PatientRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AppointmentController extends Controller
{
    public function __construct(
        protected AppointmentRepository $appointments,
        protected PatientRepository $patients,
    ) {}

    public function index(Request $request): View
    {
        $page = max(1, (int) $request->get('page', 1));
        $appointments = $this->appointments->paginateIndex(
            $page,
            15,
            $request->filled('date') ? $request->string('date')->toString() : null,
            $request->filled('status') ? $request->string('status')->toString() : null,
        );

        return view('appointments.index', compact('appointments'));
    }

    public function create(Request $request): View
    {
        $prefillPatient = null;
        $pid = (string) old('patient_id', (string) $request->query('patient_id', ''));
        if ($pid !== '') {
            $prefillPatient = $this->patients->find($pid);
        }

        return view('appointments.create', compact('prefillPatient'));
    }

    public function store(AppointmentRequest $request): RedirectResponse
    {
        $this->appointments->create($request->validated());

        return redirect()->route('appointments.index')->with('success', __('messages.appointment_created'));
    }

    public function patientsAutocomplete(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q', ''));

        $patients = $this->patients->searchLimited($q, 15)->map(fn ($p) => [
            'id' => $p->id,
            'patient_uid' => $p->patient_uid,
            'full_name' => $p->full_name,
            'phone' => $p->phone,
        ]);

        return response()->json(['data' => $patients]);
    }

    public function show(Appointment $appointment): View
    {
        return view('appointments.show', compact('appointment'));
    }

    public function edit(Appointment $appointment): View
    {
        return view('appointments.edit', compact('appointment'));
    }

    public function update(AppointmentRequest $request, Appointment $appointment): RedirectResponse
    {
        $this->appointments->update($appointment, $request->validated());

        return redirect()->route('appointments.index')->with('success', __('messages.appointment_updated'));
    }

    public function destroy(Appointment $appointment): RedirectResponse
    {
        $this->appointments->delete($appointment->id);

        return redirect()->route('appointments.index')->with('success', __('messages.appointment_deleted'));
    }

    public function updateStatus(UpdateAppointmentStatusRequest $request, Appointment $appointment): RedirectResponse
    {
        $this->appointments->update($appointment, [
            'patient_id' => $appointment->patient_id,
            'appointment_date' => $appointment->appointment_date->toDateString(),
            'appointment_time' => $appointment->appointment_time,
            'consultation_type' => $appointment->consultation_type,
            'status' => $request->validated()['status'],
            'google_calendar_event_id' => $appointment->google_calendar_event_id,
        ]);

        return back()->with('success', __('messages.appointment_status_updated'));
    }

    public function export(Request $request): StreamedResponse|Response
    {
        $filename = 'appointments_'.now()->format('Y-m-d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        return response()->streamDownload(function () use ($request) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'appointment_uid', 'patient_uid', 'patient_name', 'date', 'time', 'type', 'status', 'google_event_id',
            ]);

            $items = $this->appointments->allWithPatients()->filter(function (Appointment $a) use ($request) {
                if ($request->filled('date') && $a->appointment_date->toDateString() !== $request->string('date')->toString()) {
                    return false;
                }
                if ($request->filled('status') && $a->status !== $request->string('status')->toString()) {
                    return false;
                }
                return true;
            })->sortBy(fn (Appointment $a) => $a->id);

            foreach ($items as $a) {
                fputcsv($out, [
                    $a->appointment_uid,
                    $a->patient?->patient_uid,
                    $a->patient?->full_name,
                    $a->appointment_date?->toDateString(),
                    (string) $a->appointment_time,
                    $a->consultation_type,
                    $a->status,
                    $a->google_calendar_event_id,
                ]);
            }

            fclose($out);
        }, $filename, $headers);
    }
}
