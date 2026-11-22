<?php

use App\Models\SalaryRate;
use Database\Seeders\SalarySchedule2026Seeder;

test('official 2026 schedule imports every supplied rate without inventing SG33 steps', function () {
    $this->seed(SalarySchedule2026Seeder::class);
    expect(SalaryRate::where('year', 2026)->count())->toBe(258)
        ->and((float) SalaryRate::where('salary_grade', 'SG 1')->where('salary_step', 'Step 1')->value('monthly_salary'))->toBe(14634.0)
        ->and((float) SalaryRate::where('salary_grade', 'SG 12')->where('salary_step', 'Step 2')->value('monthly_salary'))->toBe(34069.0)
        ->and((float) SalaryRate::where('salary_grade', 'SG 33')->where('salary_step', 'Step 2')->value('monthly_salary'))->toBe(462329.0)
        ->and(SalaryRate::where('salary_grade', 'SG 33')->count())->toBe(2);
    $this->seed(SalarySchedule2026Seeder::class);
    expect(SalaryRate::count())->toBe(258);
});

test('salary import preserves existing HR rates and their inactive status', function () {
    $existing = SalaryRate::create(['year' => 2026, 'salary_grade' => 'SG 1', 'salary_step' => 'Step 1', 'monthly_salary' => 15000, 'is_active' => false]);
    $this->seed(SalarySchedule2026Seeder::class);
    expect((float) $existing->fresh()->monthly_salary)->toBe(15000.0)
        ->and($existing->fresh()->is_active)->toBeFalse()->and(SalaryRate::count())->toBe(258);
});
