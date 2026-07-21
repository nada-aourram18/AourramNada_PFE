<?php

namespace App\Http\Requests;

use App\Repositories\PatientRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'patient_id' => [$isUpdate ? 'sometimes' : 'required', 'string'],
            'appointment_date' => array_values(array_filter([
                $isUpdate ? 'sometimes' : 'required',
                'date',
                $isUpdate ? null : 'after_or_equal:today',
            ])),
            'appointment_time' => [$isUpdate ? 'sometimes' : 'required', 'date_format:H:i'],
            'consultation_type' => ['sometimes', Rule::in(['general', 'dentaire', 'autre'])],
            'status' => ['required', Rule::in(['confirme', 'en_attente', 'annule'])],
            'google_calendar_event_id' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v): void {
            $pid = $this->input('patient_id');
            if ($pid === null || $pid === '') {
                return;
            }
            if (app(PatientRepository::class)->find((string) $pid) === null) {
                $v->errors()->add('patient_id', __('validation.exists', ['attribute' => 'patient']));
            }
        });
    }
}
