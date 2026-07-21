<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingsUpdateRequest;
use App\Http\Requests\StoreReceptionistRequest;
use App\Models\Setting;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        protected UserRepository $users,
    ) {}

    public function index(Request $request): View
    {
        $openai = Setting::getValue('openai_api_key');
        $airtableKey = Setting::getValue('airtable_api_key');

        $users = $this->users->receptionists();

        return view('settings.index', [
            'n8n_webhook_url' => Setting::getValue('n8n_webhook_url', ''),
            'openai_api_key_masked' => $this->maskSecret($openai),
            'google_calendar_id' => Setting::getValue('google_calendar_id', ''),
            'airtable_base_id' => Setting::getValue('airtable_base_id', ''),
            'airtable_api_key_masked' => $this->maskSecret($airtableKey),
            'users' => $users,
        ]);
    }

    public function update(SettingsUpdateRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Setting::setValue('n8n_webhook_url', $data['n8n_webhook_url'] ?? null);
        Setting::setValue('google_calendar_id', $data['google_calendar_id'] ?? null);
        Setting::setValue('airtable_base_id', $data['airtable_base_id'] ?? null);

        if (! empty($data['openai_api_key'])) {
            Setting::setValue('openai_api_key', $data['openai_api_key']);
        }

        if (! empty($data['airtable_api_key'])) {
            Setting::setValue('airtable_api_key', $data['airtable_api_key']);
        }

        $user = $request->user();
        if (! empty($data['profile_name'])) {
            $user->name = $data['profile_name'];
        }
        if (! empty($data['profile_email'])) {
            $user->email = $data['profile_email'];
        }
        if (! empty($data['profile_password'])) {
            $user->password = $data['profile_password'];
        }
        $user->save();

        return back()->with('success', __('messages.settings_saved'));
    }

    public function storeReceptionist(StoreReceptionistRequest $request): RedirectResponse
    {
        $this->users->create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
            'role' => 'receptionist',
        ]);

        return back()->with('success', __('messages.receptionist_created'));
    }

    public function destroyReceptionist(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_if($user->id === $request->user()->id, 403);
        abort_unless($user->isReceptionist(), 403);

        $this->users->delete($user->id);

        return back()->with('success', __('messages.receptionist_deleted'));
    }

    private function maskSecret(?string $value): string
    {
        if (! $value) {
            return '';
        }

        $len = strlen($value);
        if ($len <= 4) {
            return str_repeat('•', $len);
        }

        return str_repeat('•', max(0, $len - 4)).substr($value, -4);
    }
}
