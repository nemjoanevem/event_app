<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        Event::factory()
            ->count(12)
            ->create(['status' => 'published']);

        Event::factory()
            ->count(3)
            ->create(['status' => 'draft']);
    }
}
