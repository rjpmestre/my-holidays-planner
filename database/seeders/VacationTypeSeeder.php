<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\VacationType;

class VacationTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        VacationType::create([
            'id' => 1,
            'name' => 'Regular',
            'description' => 'Férias regulares (22 dias por ano)',
            'counts_toward_limit' => true,
        ]);

        VacationType::create([
            'id' => 2,
            'name' => 'Carried',
            'description' => 'Dias transportados do ano anterior (máx. 5 dias)',
            'counts_toward_limit' => false,
        ]);

        VacationType::create([
            'id' => 3,
            'name' => 'Volunteer',
            'description' => 'Dia de voluntariado',
            'counts_toward_limit' => false,
        ]);

        VacationType::create([
            'id' => 4,
            'name' => 'Bonus',
            'description' => 'Dias extra oferecidos pela empresa',
            'counts_toward_limit' => false,
        ]);
    }
}
