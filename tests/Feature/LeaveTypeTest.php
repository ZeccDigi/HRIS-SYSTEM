<?php

use App\Models\LeaveType;

test('leave type has legal_basis field', function () {
    $leaveType = LeaveType::create([
        'name' => 'Test Leave',
        'description' => 'A test leave type description.',
        'legal_basis' => 'Sec. 1 of Republic Act No. 12345',
        'days_per_year' => 10,
    ]);

    expect($leaveType->legal_basis)->toBe('Sec. 1 of Republic Act No. 12345');
    expect($leaveType->description)->toBe('A test leave type description.');
});

test('leave type seeder creates all leave types with descriptions and legal basis', function () {
    $this->seed(\Database\Seeders\LeaveTypeSeeder::class);

    $leaveTypes = LeaveType::all();

    expect($leaveTypes)->toHaveCount(14);

    $leaveTypes->each(function ($leaveType) {
        expect($leaveType->description)->not->toBeNull();
        expect($leaveType->legal_basis)->not->toBeNull();
    });
});

test('vacation leave has correct legal basis', function () {
    $this->seed(\Database\Seeders\LeaveTypeSeeder::class);

    $vacationLeave = LeaveType::where('name', 'Vacation Leave')->first();

    expect($vacationLeave->legal_basis)->toContain('Sec. 51');
    expect($vacationLeave->legal_basis)->toContain('Executive Order No. 292');
});
