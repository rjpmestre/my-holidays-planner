<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\VacationType;
use App\Models\Vacation;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VacationTypeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that vacation types are created correctly.
     */
    public function test_vacation_types_exist(): void
    {
        $this->seed(\Database\Seeders\VacationTypeSeeder::class);

        $this->assertDatabaseHas('vacation_types', [
            'name' => 'Regular',
            'counts_toward_limit' => true,
        ]);

        $this->assertDatabaseHas('vacation_types', [
            'name' => 'Carried',
            'counts_toward_limit' => false,
        ]);

        $this->assertDatabaseHas('vacation_types', [
            'name' => 'Volunteer',
            'counts_toward_limit' => false,
        ]);

        $this->assertDatabaseHas('vacation_types', [
            'name' => 'Bonus',
            'counts_toward_limit' => false,
        ]);
    }

    /**
     * Test vacation type relationship.
     */
    public function test_vacation_has_type_relationship(): void
    {
        $this->seed(\Database\Seeders\VacationTypeSeeder::class);

        $vacation = Vacation::create([
            'date' => '2025-06-15',
            'type_id' => 1,
        ]);

        $this->assertInstanceOf(VacationType::class, $vacation->type);
        $this->assertEquals('Regular', $vacation->type->name);
    }

    /**
     * Test carried days have year_carried_from field.
     */
    public function test_carried_vacation_stores_previous_year(): void
    {
        $this->seed(\Database\Seeders\VacationTypeSeeder::class);

        $vacation = Vacation::create([
            'date' => '2025-07-20',
            'type_id' => 2, // Carried type
            'year_carried_from' => 2024,
        ]);

        $this->assertEquals(2024, $vacation->year_carried_from);
        $this->assertEquals(2, $vacation->type_id);
    }

    /**
     * Test vacation scopes work correctly.
     */
    public function test_vacation_scopes(): void
    {
        $this->seed(\Database\Seeders\VacationTypeSeeder::class);

        Vacation::create(['date' => '2025-06-01', 'type_id' => 1]); // Regular
        Vacation::create(['date' => '2025-06-02', 'type_id' => 2]); // Carried
        Vacation::create(['date' => '2025-06-03', 'type_id' => 3]); // Volunteer
        Vacation::create(['date' => '2025-06-04', 'type_id' => 4]); // Bonus

        $this->assertEquals(1, Vacation::regular()->count());
        $this->assertEquals(1, Vacation::carried()->count());
        $this->assertEquals(1, Vacation::volunteer()->count());
        $this->assertEquals(1, Vacation::bonus()->count());
    }
}
