<?php

namespace App\Livewire;

use Livewire\Component;
use Carbon\Carbon;
use App\Models\Holiday;
use App\Models\Birthday;
use App\Models\Vacation;
use App\Models\VacationType;
use App\Models\VacationSettings;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

class Calendar extends Component
{
    #[Url]
    public $currentYear;

    // related to PT laws
    public $maxVacationDays = 22;
    public $minConsecutiveVacationDays = 10;
    public $maxVolunteerDays = 1; // Volunteer day per year

    public $manualCarriedDays = 0; // Manual input for carried days from previous year (no limit)

    public $totalDaysSelected = 0;
    public $regularDaysCount = 0;
    public $carriedDaysCount = 0;
    public $volunteerDaysCount = 0;
    public $bonusDaysCount = 0;

    public $selectableDates = [];
    public $selectedDays = [];
    public $vacationDetails = []; // Store vacation data as arrays [date => ['date', 'type_id', 'year_carried_from']]

    public $selectedDayForTypeChange = null; // Track which day is being edited
    public $editingLimit = null; // Track which limit is being edited: 'regular', 'carried', 'volunteer'
    public $tempLimitValue = 0; // Temporary value while editing

    public $holidays = [];
    public $holidayGroups = [];
    public $weekends = [];
    public $birthdays = [];

    public $warnings = [];

    public function mount() {
        $this->currentYear = $this->currentYear ?? Carbon::now()->year;
        $this->loadData();
    }

    protected function loadData() {
        $this->loadSettings();
        $this->loadHolidays();
        $this->loadSelectedDays();
        $this->loadHolidayGroups();
        $this->loadWeekends();
        $this->loadBirthdays();
        $this->loadSelectableDates();
        // dd($this->holidays, $this->weekends, $this->birthdays);
    }

    protected function loadSettings() {
        $settings = VacationSettings::firstOrCreate(
            ['year' => $this->currentYear],
            [
                'max_vacation_days' => 22,
                'max_volunteer_days' => 1,
                'manual_carried_days' => 0,
                'min_consecutive_vacation_days' => 10,
            ]
        );

        $this->maxVacationDays = $settings->max_vacation_days;
        $this->maxVolunteerDays = $settings->max_volunteer_days;
        $this->manualCarriedDays = $settings->manual_carried_days;
        $this->minConsecutiveVacationDays = $settings->min_consecutive_vacation_days;
    }

    protected function saveSettings() {
        VacationSettings::updateOrCreate(
            ['year' => $this->currentYear],
            [
                'max_vacation_days' => $this->maxVacationDays,
                'max_volunteer_days' => $this->maxVolunteerDays,
                'manual_carried_days' => $this->manualCarriedDays,
                'min_consecutive_vacation_days' => $this->minConsecutiveVacationDays,
            ]
        );
    }

    protected function loadHolidays() {
        $this->holidays = Holiday::whereYear('date', $this->currentYear)
            ->with('type')
            ->get()
            ->groupBy(function ($holiday) {
                return Carbon::parse($holiday->date)->format('Y-m-d');
            })->all();
    }

    protected function loadBirthdays() {
        $this->birthdays = Birthday::whereYear('date', $this->currentYear)
            ->get()
            ->groupBy(function ($holiday) {
                return Carbon::parse($holiday->date)->format('Y-m-d');
            })->all();
    }

    protected function loadSelectedDays() {
        $vacations = Vacation::whereYear('date', $this->currentYear)
            ->with('type')
            ->get();

        $this->selectedDays = $vacations->pluck('date')
            ->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })->toArray();

        // Store vacation details as simple arrays (Livewire-friendly)
        $this->vacationDetails = [];
        foreach ($vacations as $vacation) {
            $date = Carbon::parse($vacation->date)->format('Y-m-d');
            $this->vacationDetails[$date] = [
                'date' => $date,
                'type_id' => $vacation->type_id,
                'year_carried_from' => $vacation->year_carried_from
            ];
        }

        $this->totalDaysSelected = count($this->selectedDays);

        // Count by type
        $this->regularDaysCount = $vacations->where('type_id', 1)->count();
        $this->carriedDaysCount = $vacations->where('type_id', 2)->count();
        $this->volunteerDaysCount = $vacations->where('type_id', 3)->count();
        $this->bonusDaysCount = $vacations->where('type_id', 4)->count();
    }

    protected function loadHolidayGroups() {
        $this->holidayGroups = Holiday::whereYear('date', $this->currentYear)
        ->with('type')
        ->whereNotNull('group_id')
        ->get()
        ->groupBy('group_id')
        ->map(function ($holidays) {
            return [
                'dates' => $holidays->pluck('date')->map(function ($date) {
                    return Carbon::parse($date)->format('Y-m-d');
                })->toArray(),
            ];
        })->values()->toArray(); // Convert to an array of groups
    }

    protected function loadSelectableDates() {
        $nonMovable = Holiday::whereYear('date', $this->currentYear)
            ->whereNull('group_id')
            ->get()
            ->groupBy(function ($holiday) {
                return Carbon::parse($holiday->date)->format('Y-m-d');
            })->all();
        $nonMovableDates = array_keys($nonMovable);
        $nonSelectableDays = array_merge($this->weekends, $nonMovableDates);

        $this->selectableDates = [];

        $startOfYear = Carbon::createFromDate($this->currentYear, 1, 1);
        $endOfYear = Carbon::createFromDate($this->currentYear, 12, 31);

        for ($date = $startOfYear->copy(); $date->lte($endOfYear); $date->addDay()) {
            $formattedDate = $date->format('Y-m-d');

            if (!in_array($formattedDate, $nonSelectableDays)) {
                $this->selectableDates[] = $formattedDate;
            }
        }
    }

    public function getClasses($date) {
        if (in_array($date, $this->selectedDays)){
            return implode(' ', ['bg-warning', 'font-weight-bold']);
        }
        if($this->isNationalHoliday($date)){
            return 'national_holiday';
        }
        if($this->isAlternativeHoliday($date)){
            return 'alternative_holiday';
        }
        if($this->isCompanyHoliday($date)){
            return 'company_holiday';
        }
        if(count($this->getBirthDays($date))>0){
            return 'birthday';
        }
        if($this->isWeekend($date)){
            return 'weekend';
        }
        return 'selectable_day';
    }

    public function toggleDay($date, $typeId = 1) {
        if (!$this->isSelectableDate($date)) {
            return;
        }

        if (in_array($date, $this->selectedDays)) {
            // Remove day
            $this->selectedDays = array_diff($this->selectedDays, [$date]);
            unset($this->vacationDetails[$date]);
            \Log::alert("Removed day: {$date}, selectedDays count: " . count($this->selectedDays) . ", vacationDetails count: " . count($this->vacationDetails));
        } else {
            // Add day
            $this->selectedDays[] = $date;

            // Store vacation details as simple array (Livewire-friendly)
            $this->vacationDetails[$date] = [
                'date' => $date,
                'type_id' => $typeId,
                'year_carried_from' => $typeId == 2 ? $this->currentYear - 1 : null
            ];

            \Log::alert("Added day: {$date}, typeId: {$typeId}, selectedDays count: " . count($this->selectedDays) . ", vacationDetails count: " . count($this->vacationDetails));
        }

        // Force re-index the array to avoid gaps
        $this->selectedDays = array_values($this->selectedDays);
    }

    public function changeVacationType($date, $typeId) {
        if (isset($this->vacationDetails[$date])) {
            // Update vacation details array
            $this->vacationDetails[$date]['type_id'] = $typeId;
            $this->vacationDetails[$date]['year_carried_from'] = $typeId == 2 ? $this->currentYear - 1 : null;

            $this->dispatch('toast', ['type' => 'success', 'message' => 'Tipo de férias alterado!']);
        }
    }

    public function setManualCarriedDays() {
        if ($this->manualCarriedDays < 0) {
            $this->manualCarriedDays = 0;
        }

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'Pode selecionar '.$this->manualCarriedDays.' dias transportados. Selecione os dias e altere o tipo para "Transportado".'
        ]);
    }

    public function editLimit($type) {
        $this->editingLimit = $type;

        switch($type) {
            case 'regular':
                $this->tempLimitValue = $this->maxVacationDays;
                break;
            case 'carried':
                $this->tempLimitValue = $this->manualCarriedDays;
                break;
            case 'volunteer':
                $this->tempLimitValue = $this->maxVolunteerDays;
                break;
        }
    }

    public function saveLimit() {
        if ($this->tempLimitValue < 0) {
            $this->tempLimitValue = 0;
        }

        switch($this->editingLimit) {
            case 'regular':
                $this->maxVacationDays = $this->tempLimitValue;
                break;
            case 'carried':
                $this->manualCarriedDays = $this->tempLimitValue;
                break;
            case 'volunteer':
                $this->maxVolunteerDays = $this->tempLimitValue;
                break;
        }

        $this->saveSettings();
        $this->editingLimit = null;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Limite atualizado!']);
    }

    public function cancelEdit() {
        $this->editingLimit = null;
        $this->tempLimitValue = 0;
    }

    public function isNationalHoliday($date) {
        if(!isset($this->holidays[$date])){
            return false;
        }
        return $this->holidays[$date][0]->type_id === 1 && $this->holidays[$date][0]->group_id === null;
    }

    public function isAlternativeHoliday($date) {
        if(!isset($this->holidays[$date])){
            return false;
        }
        return $this->holidays[$date][0]->group_id !== null;
    }

    public function isCompanyHoliday($date) {
        if(!isset($this->holidays[$date])){
            return false;
        }
        return $this->holidays[$date][0]->type_id === 2;
    }

    public function getBirthDays($date) {
        return $this->birthdays[$date] ?? collect();
    }

    public function isSelectableDate($date) {
        return in_array($date, $this->selectableDates);
    }

    public function isWeekend($date) {
        return in_array($date, $this->weekends);
    }

    public function getHolidays($date){
        return $this->holidays[$date] ?? collect();
    }

    public function restoreVacationDays(){
        $this->loadSelectedDays();
    }

    public function clearVacationDays() {
        $this->selectedDays = [];
        $this->vacationDetails = [];
    }

    public function saveVacationDays(){
        Vacation::whereYear('date', $this->currentYear)->delete();
        foreach ($this->selectedDays as $day) {
            $typeId = $this->vacationDetails[$day]['type_id'] ?? 1;
            $yearCarriedFrom = $this->vacationDetails[$day]['year_carried_from'] ?? null;

            Vacation::create([
                'date' => $day,
                'type_id' => $typeId,
                'year_carried_from' => $yearCarriedFrom,
            ]);
        }

        // Reload data after saving to ensure UI is in sync with database
        $this->loadSelectedDays();

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Calendário guardado com sucesso!']);
    }

    public function previousYear() {
        $this->currentYear--;
        $this->loadData();
    }

    public function nextYear() {
        $this->currentYear++;
        $this->loadData();
    }

    public function render() {
        // Recalculate counts from vacationDetails
        $this->totalDaysSelected = count($this->selectedDays);

        // Count vacation types from vacationDetails array
        $this->regularDaysCount = 0;
        $this->carriedDaysCount = 0;
        $this->volunteerDaysCount = 0;
        $this->bonusDaysCount = 0;

        Log::alert("render() called - vacationDetails count: " . count($this->vacationDetails) . ", selectedDays count: " . count($this->selectedDays));

        foreach ($this->vacationDetails as $date => $vacationData) {
            $typeId = $vacationData['type_id'];
            Log::debug("Processing vacation for date {$date}, type_id: {$typeId}");
            switch ($typeId) {
                case 1:
                    $this->regularDaysCount++;
                    break;
                case 2:
                    $this->carriedDaysCount++;
                    break;
                case 3:
                    $this->volunteerDaysCount++;
                    break;
                case 4:
                    $this->bonusDaysCount++;
                    break;
            }
        }

        Log::debug("Final counts - Regular: {$this->regularDaysCount}, Carried: {$this->carriedDaysCount}, Volunteer: {$this->volunteerDaysCount}, Bonus: {$this->bonusDaysCount}");

        $this->checkWarnings();
        return view('livewire.calendar');
    }

    public function checkWarnings() {
        $this->warnings = [];

        // Check regular vacation days limit
        if ($this->regularDaysCount > $this->maxVacationDays) {
            $this->warnings[] = 'O número de dias de férias regulares selecionados ('.$this->regularDaysCount.') excede o limite de '.$this->maxVacationDays.' dias.';
        }

        // Check carried days limit only if manual input is set
        if ($this->manualCarriedDays > 0 && $this->carriedDaysCount > $this->manualCarriedDays) {
            $this->warnings[] = 'O número de dias transportados ('.$this->carriedDaysCount.') excede o limite definido de '.$this->manualCarriedDays.' dias.';
        }

        // Check volunteer days limit
        if ($this->volunteerDaysCount > $this->maxVolunteerDays) {
            $this->warnings[] = 'Só pode selecionar '.$this->maxVolunteerDays.' dia de voluntariado por ano.';
        }

        // Check consecutive vacation days (only for regular vacation days)
        if (!$this->hasConsecutiveVacationDays($this->minConsecutiveVacationDays)) {
            $this->warnings[] = 'O gozo do período de férias pode ser interpolado, por acordo entre empregador e trabalhador, desde que
sejam gozados, no mínimo, 10 dias úteis consecutivo.';
        }

        log::alert('checkWarnings : '.count($this->warnings));
    }

    public function selectBridges($maxDays) {
        $this->selectedDays = [];

        $nonMovableHolidays = Holiday::whereYear('date', $this->currentYear)->whereNull('group_id')
        ->with('type')
        ->get()
        ->groupBy(function ($holiday) {
            return Carbon::parse($holiday->date)->format('Y-m-d');
        })->all();
        $holidayDates = collect(array_keys($nonMovableHolidays));

        // Merge selected vacation days, weekends, and holidays to create "off days"
        $offDays = collect($this->selectedDays)
            ->merge($holidayDates)
            ->merge($this->weekends)
            ->sort()
            ->unique()
            ->values();

        $daysToFill = [];

        // Iterate through the off days to find the gaps
        for ($i = 0; $i < count($offDays) - 1; $i++) {
            $currOffDay = Carbon::parse($offDays[$i]);
            $nextOffDay = Carbon::parse($offDays[$i + 1]);

            // Calculate the difference between two off days
            $daysBetween = $currOffDay->diffInDays($nextOffDay) - 1;

            // Check if the number of days between the two offdays is less than or equal to $maxDays
            if ($daysBetween > 0 && $daysBetween <= $maxDays) {
                $bridgeDays = [];
                for ($date = $currOffDay->copy()->addDay(); $date->lt($nextOffDay); $date->addDay()) {
                    $bridgeDays[] = $date->format('Y-m-d');
                }
                array_push($daysToFill, ...$bridgeDays);
            }
        }

        // Add the bridge days to the selected vacation days
        foreach ($daysToFill as $bridgeDay) {
            if (!in_array($bridgeDay, $this->selectedDays)) {
                $this->selectedDays[] = $bridgeDay;

                // Add to vacationDetails with default type (regular vacation)
                $this->vacationDetails[$bridgeDay] = [
                    'date' => $bridgeDay,
                    'type_id' => 1, // Regular vacation
                    'year_carried_from' => null
                ];
            }
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Pontes até '.$maxDays.' dias selecionadas.']);
    }

    protected function loadWeekends ()
    {
        $this->weekends = [];

        // Loop through all the days of the year
        $startDate = Carbon::createFromDate($this->currentYear, 1, 1);
        $endDate = Carbon::createFromDate($this->currentYear, 12, 31);

        while ($startDate->lte($endDate)) {
            // Check if the day is Saturday or Sunday
            if ($startDate->isWeekend()) {
                $this->weekends[] = $startDate->format('Y-m-d');
            }
            $startDate->addDay();
        }
    }

    public function hasConsecutiveVacationDays(int $minDays)
    {
        // Ensure selectableDates are sorted in ascending order
        $consecutiveCount = 0;

        // Iterate through the sorted selectable dates
        for ($i = 0; $i < count($this->selectableDates) - 1; $i++) {
            $currentDay = Carbon::parse($this->selectableDates[$i]);
            $nextDay = Carbon::parse($this->selectableDates[$i + 1]);

            // If both currentDay and nextDay are selected and no non-working days in between
            if (in_array($currentDay->format('Y-m-d'), $this->selectedDays) &&
                in_array($nextDay->format('Y-m-d'), $this->selectedDays)) {

                $consecutiveCount++;
            } else {
                // Reset the count if we find a break
                $consecutiveCount = 0;
            }

            // If the count reaches or exceeds $minDays - 1 (minDays consecutive)
            if ($consecutiveCount >= $minDays - 1) {
                return true;  // There are at least $minDays consecutive vacation days
            }
        }

        // If no such block of days is found, return false
        return false;
    }

    public function getVacationType($date) {
        if (isset($this->vacationDetails[$date])) {
            return VacationType::find($this->vacationDetails[$date]['type_id']);
        }
        return null;
    }

}
