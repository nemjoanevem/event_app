<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\EventSeeder;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Ensure there are some events and users to associate bookings with
        if (Event::count() === 0) {
            $this->call(EventSeeder::class);
        }

        if (User::count() < 5) {
            User::factory()->count(5)->create();
        }

        Booking::factory()->count(30)->create();
    }
}
