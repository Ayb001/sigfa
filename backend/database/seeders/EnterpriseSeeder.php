<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Enterprise;
use App\Models\Queue;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EnterpriseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Enterprise 1: Banque ──────────────────────────────────────────────
        $bna = Enterprise::create([
            'name'             => 'Banque Nationale Algérienne',
            'sector'           => 'banque',
            'address'          => '12, Rue Didouche Mourad',
            'city'             => 'Alger',
            'contact_email'    => 'contact@bna.dz',
            'contact_phone'    => '+213 21 63 54 00',
            'default_language' => 'fr',
            'status'           => 'active',
        ]);

        $bnaAdmin = User::create([
            'tenant_id'           => $bna->id,
            'first_name'          => 'Karim',
            'last_name'           => 'Benali',
            'email'               => 'admin@bna.dz',
            'phone'               => '+213 770 123 456',
            'password'            => 'password',
            'role'                => 'enterprise_admin',
            'language_preference' => 'fr',
            'status'              => 'active',
        ]);

        $bnaBranch1 = Branch::create([
            'tenant_id'     => $bna->id,
            'name'          => 'Agence Didouche Mourad',
            'address'       => '12, Rue Didouche Mourad',
            'city'          => 'Alger',
            'phone'         => '+213 21 63 54 01',
            'opening_hours' => [
                ['day' => 'Dimanche',  'open' => '08:30', 'close' => '15:30'],
                ['day' => 'Lundi',     'open' => '08:30', 'close' => '15:30'],
                ['day' => 'Mardi',     'open' => '08:30', 'close' => '15:30'],
                ['day' => 'Mercredi',  'open' => '08:30', 'close' => '15:30'],
                ['day' => 'Jeudi',     'open' => '08:30', 'close' => '15:30'],
            ],
            'status' => 'active',
        ]);

        $bnaBranch2 = Branch::create([
            'tenant_id'     => $bna->id,
            'name'          => 'Agence Bab Ezzouar',
            'address'       => 'Zone Industrielle Bab Ezzouar',
            'city'          => 'Alger',
            'phone'         => '+213 21 63 54 02',
            'opening_hours' => [
                ['day' => 'Dimanche', 'open' => '08:00', 'close' => '15:00'],
                ['day' => 'Lundi',    'open' => '08:00', 'close' => '15:00'],
            ],
            'status' => 'active',
        ]);

        // Employees for BNA
        $employees = [
            ['first_name' => 'Sofiane', 'last_name' => 'Meziani',   'email' => 'sofiane.m@bna.dz'],
            ['first_name' => 'Nadia',   'last_name' => 'Cherif',     'email' => 'nadia.c@bna.dz'],
            ['first_name' => 'Rachid',  'last_name' => 'Hamidouche', 'email' => 'rachid.h@bna.dz'],
        ];

        $bnaEmployees = [];
        foreach ($employees as $emp) {
            $bnaEmployees[] = User::create([
                'tenant_id'           => $bna->id,
                'branch_id'           => $bnaBranch1->id,
                'first_name'          => $emp['first_name'],
                'last_name'           => $emp['last_name'],
                'email'               => $emp['email'],
                'password'            => 'password',
                'role'                => 'employee',
                'language_preference' => 'fr',
                'status'              => 'active',
            ]);
        }

        // Queues for BNA branch 1
        $qGuichet = Queue::create([
            'tenant_id'        => $bna->id,
            'branch_id'        => $bnaBranch1->id,
            'name'             => 'Guichet Standard',
            'prefix'           => 'A',
            'avg_service_time' => 7,
            'priority_rules'   => ['enabled' => true, 'qualifiers' => ['personnes âgées', 'femmes enceintes', 'handicapés']],
            'status'           => 'active',
        ]);

        $qCredit = Queue::create([
            'tenant_id'        => $bna->id,
            'branch_id'        => $bnaBranch1->id,
            'name'             => 'Crédits & Prêts',
            'prefix'           => 'B',
            'avg_service_time' => 15,
            'priority_rules'   => ['enabled' => false],
            'status'           => 'active',
        ]);

        // ─── Enterprise 2: Hôpital ────────────────────────────────────────────
        $chu = Enterprise::create([
            'name'             => 'CHU Mustapha Pacha',
            'sector'           => 'hopital',
            'address'          => 'Place du 1er Mai',
            'city'             => 'Alger',
            'contact_email'    => 'accueil@chu-mustapha.dz',
            'contact_phone'    => '+213 21 23 54 32',
            'default_language' => 'fr',
            'status'           => 'active',
        ]);

        User::create([
            'tenant_id'           => $chu->id,
            'first_name'          => 'Amina',
            'last_name'           => 'Kaci',
            'email'               => 'admin@chu-mustapha.dz',
            'password'            => 'password',
            'role'                => 'enterprise_admin',
            'language_preference' => 'fr',
            'status'              => 'active',
        ]);

        $chuBranch = Branch::create([
            'tenant_id'     => $chu->id,
            'name'          => 'Urgences — Bâtiment A',
            'address'       => 'Place du 1er Mai, Bâtiment A',
            'city'          => 'Alger',
            'phone'         => '+213 21 23 54 33',
            'opening_hours' => [['day' => 'Tous les jours', 'open' => '00:00', 'close' => '23:59']],
            'status'        => 'active',
        ]);

        $chuEmployee = User::create([
            'tenant_id'           => $chu->id,
            'branch_id'           => $chuBranch->id,
            'first_name'          => 'Mohamed',
            'last_name'           => 'Aït Yahia',
            'email'               => 'mohamed.ay@chu-mustapha.dz',
            'password'            => 'password',
            'role'                => 'employee',
            'language_preference' => 'fr',
            'status'              => 'active',
        ]);

        Queue::create([
            'tenant_id'        => $chu->id,
            'branch_id'        => $chuBranch->id,
            'name'             => 'Accueil Urgences',
            'prefix'           => 'U',
            'avg_service_time' => 10,
            'priority_rules'   => ['enabled' => true, 'qualifiers' => ['cas critiques']],
            'status'           => 'active',
        ]);

        Queue::create([
            'tenant_id'        => $chu->id,
            'branch_id'        => $chuBranch->id,
            'name'             => 'Consultations Externes',
            'prefix'           => 'C',
            'avg_service_time' => 20,
            'priority_rules'   => ['enabled' => false],
            'status'           => 'active',
        ]);

        // ─── Enterprise 3: Administration (pending approval) ──────────────────
        Enterprise::create([
            'name'             => 'Mairie de Tizi Ouzou',
            'sector'           => 'administration',
            'address'          => 'Rue de la Mairie',
            'city'             => 'Tizi Ouzou',
            'contact_email'    => 'mairie@tizi-ouzou.dz',
            'contact_phone'    => '+213 26 42 10 00',
            'default_language' => 'fr',
            'status'           => 'pending',
        ]);

        // ─── Demo Clients ─────────────────────────────────────────────────────
        $clients = [
            ['first_name' => 'Yasmine',   'last_name' => 'Boukhari',  'email' => 'yasmine.b@email.com',  'phone' => '+213 550 001 001'],
            ['first_name' => 'Djamel',    'last_name' => 'Saadi',     'email' => 'djamel.s@email.com',   'phone' => '+213 550 001 002'],
            ['first_name' => 'Fatima',    'last_name' => 'Zerrouki',  'email' => 'fatima.z@email.com',   'phone' => '+213 550 001 003'],
            ['first_name' => 'Arezki',    'last_name' => 'Titouh',    'email' => 'arezki.t@email.com',   'phone' => '+213 550 001 004'],
            ['first_name' => 'Sabrina',   'last_name' => 'Merakchi',  'email' => 'sabrina.m@email.com',  'phone' => '+213 550 001 005'],
        ];

        $clientModels = [];
        foreach ($clients as $c) {
            $clientModels[] = Client::create([
                'first_name'          => $c['first_name'],
                'last_name'           => $c['last_name'],
                'email'               => $c['email'],
                'phone'               => $c['phone'],
                'password'            => 'password',
                'language_preference' => 'fr',
                'status'              => 'active',
            ]);
        }

        // ─── Demo historical tickets for BNA (past week) ──────────────────────
        $statuses = ['served', 'served', 'served', 'skipped', 'cancelled'];
        foreach (range(1, 40) as $i) {
            $createdAt = Carbon::now()->subDays(rand(0, 6))->setTime(rand(8, 14), rand(0, 59));
            $calledAt  = (clone $createdAt)->addMinutes(rand(5, 20));
            $servedAt  = (clone $calledAt)->addMinutes(rand(3, 12));
            $status    = $statuses[array_rand($statuses)];
            $client    = $clientModels[array_rand($clientModels)];

            Ticket::create([
                'tenant_id'     => $bna->id,
                'queue_id'      => $qGuichet->id,
                'client_id'     => $client->id,
                'ticket_number' => 'A' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'status'        => $status,
                'priority'      => rand(0, 4) === 0 ? 'priority' : 'normal',
                'position'      => 0,
                'called_at'     => $calledAt,
                'served_at'     => $status === 'served' ? $servedAt : null,
                'created_at'    => $createdAt,
                'updated_at'    => $servedAt,
            ]);
        }

        // ─── A few waiting tickets right now ─────────────────────────────────
        foreach (range(1, 3) as $pos) {
            Ticket::create([
                'tenant_id'     => $bna->id,
                'queue_id'      => $qGuichet->id,
                'client_id'     => $clientModels[$pos - 1]->id,
                'ticket_number' => 'A' . str_pad(100 + $pos, 3, '0', STR_PAD_LEFT),
                'status'        => 'waiting',
                'priority'      => 'normal',
                'position'      => $pos,
            ]);
        }
    }
}
