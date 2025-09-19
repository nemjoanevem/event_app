<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Enums\RoleEnum;

class DemoUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@eventhub.local',
            'password' => Hash::make('Admin123!'),
            'role' => RoleEnum::ADMIN,
        ]);
        
        User::factory()->create([
            'name' => 'Organizer User',
            'email' => 'org@eventhub.local',
            'password' => Hash::make('Org123!'),
            'role' => RoleEnum::ORGANIZER,
        ]);

        User::factory()->create([
            'name' => 'Regular User',
            'email' => 'user@eventhub.local',
            'password' => Hash::make('User123!'),
            'role' => RoleEnum::USER,
        ]);
    }
}
