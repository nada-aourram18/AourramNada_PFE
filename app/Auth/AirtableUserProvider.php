<?php

namespace App\Auth;

use App\Repositories\UserRepository;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Hash;

class AirtableUserProvider implements UserProvider
{
    public function __construct(protected UserRepository $users) {}

    public function retrieveById($identifier): ?Authenticatable
    {
        if (! is_string($identifier) || $identifier === '') {
            return null;
        }

        return $this->users->find($identifier);
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        $user = $this->retrieveById($identifier);
        if ($user === null) {
            return null;
        }
        $remember = $user->getRememberToken();
        if ($remember === null || ! hash_equals($remember, $token)) {
            return null;
        }

        return $user;
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {
        $user->setRememberToken($token);
        $this->users->update($user);
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (! isset($credentials['email'])) {
            return null;
        }

        return $this->users->findByEmail((string) $credentials['email']);
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        if (! isset($credentials['password']) || ! is_string($credentials['password'])) {
            return false;
        }

        return Hash::check($credentials['password'], $user->getAuthPassword());
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        if (! isset($credentials['password']) || ! is_string($credentials['password'])) {
            return;
        }

        if (! Hash::needsRehash($user->getAuthPassword())) {
            return;
        }

        $user->password = $credentials['password'];
        $this->users->update($user);
    }
}
