<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Vacation;
use App\Models\VacationType;
use Livewire\Livewire;
use App\Livewire\Calendar;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CalendarVacationTypesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\VacationTypeSeeder::class);
        $this->seed(\Database\Seeders\HolidayTypeSeeder::class);
    }

    /**
     * Test calendar loads with vacation type counts.
     */
    public function test_calendar_displays_vacation_type_counts(): void
    {
        Vacation::create(['date' => '2025-06-15', 'type_id' => 1]); // Regular
        Vacation::create(['date' => '2025-06-16', 'type_id' => 2]); // Carried
        Vacation::create(['date' => '2025-06-17', 'type_id' => 3]); // Volunteer

        Livewire::test(Calendar::class, ['currentYear' => 2025])
            ->assertSet('regularDaysCount', 1)
            ->assertSet('carriedDaysCount', 1)
            ->assertSet('volunteerDaysCount', 1)
            ->assertSet('totalDaysSelected', 3);
    }

    /**
     * Test warnings for exceeding regular vacation days.
     */
    public function test_warning_when_exceeding_regular_vacation_limit(): void
    {
        // Create 23 regular vacation days (exceeds 22 limit)
        for ($i = 1; $i <= 23; $i++) {
            Vacation::create([
                'date' => sprintf('2025-06-%02d', $i),
                'type_id' => 1,
            ]);
        }

        Livewire::test(Calendar::class, ['currentYear' => 2025])
            ->assertSet('regularDaysCount', 23)
            ->call('render')
            ->assertSee('excede o limite de 22 dias');
    }

    /**
     * Test warnings for exceeding carried days limit.
     */
    public function test_warning_when_exceeding_carried_days_limit(): void
    {
        // Create 6 carried days (exceeds 5 limit)
        for ($i = 1; $i <= 6; $i++) {
            Vacation::create([
                'date' => sprintf('2025-07-%02d', $i),
                'type_id' => 2,
                'year_carried_from' => 2024,
            ]);
        }

        Livewire::test(Calendar::class, ['currentYear' => 2025])
            ->assertSet('carriedDaysCount', 6)
            ->call('render')
            ->assertSee('excede o limite de 5 dias');
    }

    /**
     * Test warnings for exceeding volunteer days limit.
     */
    public function test_warning_when_exceeding_volunteer_days_limit(): void
    {
        Vacation::create(['date' => '2025-08-01', 'type_id' => 3]);
        Vacation::create(['date' => '2025-08-02', 'type_id' => 3]);

        Livewire::test(Calendar::class, ['currentYear' => 2025])
            ->assertSet('volunteerDaysCount', 2)
            ->call('render')
            ->assertSee('Só pode selecionar 1 dia de voluntariado');
    }

    /**
     * Test saving vacations preserves types.
     */
    public function test_saving_vacations_preserves_types(): void
    {
        $component = Livewire::test(Calendar::class, ['currentYear' => 2025])
            ->call('toggleDay', '2025-06-15', 1)
            ->call('toggleDay', '2025-06-16', 2)
            ->call('saveVacationDays');

        $this->assertDatabaseHas('vacations', [
            'date' => '2025-06-15',
            'type_id' => 1,
        ]);
    }

    /**
     * Test carry days calculation.
     */
    public function test_carry_days_from_previous_year_calculation(): void
    {
        // Create 20 regular vacation days for 2024 (2 unused)
        for ($i = 1; $i <= 20; $i++) {
            Vacation::create([
                'date' => sprintf('2024-06-%02d', $i),
                'type_id' => 1,
            ]);
        }

        Livewire::test(Calendar::class, ['currentYear' => 2025])
            ->call('carryDaysFromPreviousYear')
            ->assertDispatched('toast');
    }
}
