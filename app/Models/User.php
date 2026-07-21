<?php

namespace App\Models;

use App\Models\Concerns\RoutesAsAirtableRecord;
use App\Repositories\UserRepository;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class User implements Authenticatable, UrlRoutable
{
    use AuthenticatableTrait, Notifiable, RoutesAsAirtableRecord;

    protected static function routeBindingRepository(): string
    {
        return UserRepository::class;
    }

    /**
     * Le trait Authenticatable appelle getKeyName() (Eloquent) ; nos utilisateurs sont des objets Airtable.
     */
    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public string $id = '';

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = 'receptionist';

    public ?string $phone = null;

    public ?string $clinic_name = null;

    public ?string $specialty = null;

    public ?string $avatar_path = null;

    public string $theme = 'light';

    public ?string $remember_token = null;

    public function isAdmin(): bool
    {
        if ($this->role === 'admin') {
            return true;
        }

        $emails = config('services.airtable.admin_emails', ['admin@clinic.com']);

        return in_array(Str::lower($this->email), array_map('strtolower', $emails), true);
    }

    public function isReceptionist(): bool
    {
        if (filled(config('services.airtable.fields.user_role'))) {
            return $this->role === 'receptionist';
        }

        return ! $this->isAdmin();
    }

    public function save(): bool
    {
        app(UserRepository::class)->update($this);

        return true;
    }

    public function avatarUrl(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        return Storage::disk('public')->url($this->avatar_path);
    }

    public function hasAvatar(): bool
    {
        return filled($this->avatar_path) && Storage::disk('public')->exists($this->avatar_path);
    }
}
