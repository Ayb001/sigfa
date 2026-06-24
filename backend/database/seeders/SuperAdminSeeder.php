<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@sigfa.fr'],
            [
                'first_name'          => 'Administrateur',
                'last_name'           => 'SIGFA',
                'password'            => 'password',
                'role'                => 'super_admin',
                'language_preference' => 'fr',
                'status'              => 'active',
            ]
        );
    }
}
