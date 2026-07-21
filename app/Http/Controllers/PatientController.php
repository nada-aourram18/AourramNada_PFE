<?php

namespace App\Http\Controllers;

use App\Http\Requests\PatientRequest;
use App\Models\Patient;
use App\Repositories\AppointmentRepository;
use App\Repositories\PatientRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PatientController extends Controller
{
    public function __construct(
        protected PatientRepository $patients,
        protected AppointmentRepository $appointments,
    ) {}

    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));
        $page = max(1, (int) $request->get('page', 1));
        $patients = $this->patients->paginateIndex($page, 15, $q);

        return view('patients.index', compact('patients', 'q'));
    }

    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q', ''));

        $patients = $this->patients->searchLimited($q, 20)->map(fn (Patient $p) => [
            'id' => $p->id,
            'patient_uid' => $p->patient_uid,
            'full_name' => $p->full_name,
            'phone' => $p->phone,
            'language' => $p->language,
            'created_at' => $p->created_at?->toIso8601String(),
        ]);

        return response()->json(['data' => $patients]);
    }

    public function show(Patient $patient): View
    {
        $page = max(1, (int) request()->get('page', 1));
        $appointments = $this->appointments->paginateForPatient($patient->id, $page, 10);

        return view('patients.show', compact('patient', 'appointments'));
    }

    public function edit(Patient $patient): View
    {
        return view('patients.edit', compact('patient'));
    }

    public function update(PatientRequest $request, Patient $patient): RedirectResponse
    {
        $this->patients->update($patient, $request->validated());

        return redirect()->route('patients.show', $patient)->with('success', __('messages.patient_updated'));
    }

    public function destroy(Request $request, Patient $patient): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $this->patients->delete($patient->id);

        return redirect()->route('patients.index')->with('success', __('messages.patient_deleted'));
    }

    public function export(Request $request): StreamedResponse|Response
    {
        abort_unless($request->user(), 403);

        $q = trim((string) $request->get('q', ''));

        $filename = 'patients_'.now()->format('Y-m-d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        return response()->streamDownload(function () use ($q) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['patient_uid', 'full_name', 'phone', 'language', 'notes', 'created_at']);

            $items = $this->patients->allAsCollection()->filter(function (Patient $p) use ($q) {
                if ($q === '') {
                    return true;
                }
                $needle = strtolower($q);

                return str_contains(strtolower($p->full_name), $needle)
                    || str_contains(strtolower($p->phone), $needle);
            })->sortBy(fn (Patient $p) => $p->id);

            foreach ($items as $p) {
                fputcsv($out, [
                    $p->patient_uid,
                    $p->full_name,
                    $p->phone,
                    $p->language,
                    $p->notes,
                    $p->created_at?->toDateTimeString(),
                ]);
            }

            fclose($out);
        }, $filename, $headers);
    }
}
