<?php

namespace Database\Seeders;

use App\Models\SalaryRate;
use App\Support\UtilityOptionRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalarySchedule2026Seeder extends Seeder
{
    public function run(): void
    {
        $schedule = json_decode(file_get_contents(database_path('data/salary-schedule-2026.json')), true, 512, JSON_THROW_ON_ERROR);
        UtilityOptionRegistry::ensureDefaults();
        $created = 0;
        $existing = 0;
        $conflicts = [];
        DB::transaction(function () use ($schedule, &$created, &$existing, &$conflicts) {
            foreach ($schedule['grades'] as $grade) {
                foreach ($grade['steps'] as $index => $amount) {
                    $key = ['year' => $schedule['year'], 'salary_grade' => 'SG '.$grade['grade'], 'salary_step' => 'Step '.($index + 1)];
                    $rate = SalaryRate::firstOrCreate($key, ['monthly_salary' => $amount, 'is_active' => true]);
                    if ($rate->wasRecentlyCreated) $created++;
                    else {
                        $existing++;
                        if ((float) $rate->monthly_salary !== (float) $amount) $conflicts[] = $key['salary_grade'].' / '.$key['salary_step'];
                    }
                }
            }
        });
        $this->command?->info("2026 salary schedule: {$created} added, {$existing} existing rates retained.");
        foreach ($conflicts as $key) $this->command?->warn("Existing amount differs from Annex A and was preserved: {$key}");
    }
}
