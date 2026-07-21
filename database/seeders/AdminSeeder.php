<?php

namespace Database\Seeders;

use App\Repositories\UserRepository;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        app(UserRepository::class)->updateOrCreateByEmail('admin@clinic.com', [
            'name' => 'Administrateur',
            'password' => 'password',
            'role' => 'admin',
        ]);
    }
}
