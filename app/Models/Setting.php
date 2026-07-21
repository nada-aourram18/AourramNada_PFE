<?php

namespace App\Models;

use App\Repositories\SettingRepository;

class Setting
{
    public static function getValue(string $key, ?string $default = null): ?string
    {
        return app(SettingRepository::class)->get($key, $default);
    }

    public static function setValue(string $key, ?string $value): void
    {
        app(SettingRepository::class)->set($key, $value);
    }
}
