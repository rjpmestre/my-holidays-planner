<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call([
            HolidayTypeSeeder::class,
            VacationTypeSeeder::class,
            HolidaySeeder::class,
            BirthdaySeeder::class,
            VacationSeeder::class,
        ]);
    }
}
