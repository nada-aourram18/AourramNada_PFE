<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingsUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'n8n_webhook_url' => ['nullable', 'url', 'max:2048'],
            'openai_api_key' => ['nullable', 'string', 'max:500'],
            'google_calendar_id' => ['nullable', 'string', 'max:500'],
            'airtable_base_id' => ['nullable', 'string', 'max:200'],
            'airtable_api_key' => ['nullable', 'string', 'max:500'],
            'profile_name' => ['nullable', 'string', 'max:255'],
            'profile_email' => ['nullable', 'email', 'max:255'],
            'profile_password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }
}
