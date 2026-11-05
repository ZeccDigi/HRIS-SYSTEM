<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PdsChild;
use App\Models\PdsEducation;
use App\Models\PdsEligibility;
use App\Models\PdsFamilyBackground;
use App\Models\PdsGovernmentId;
use App\Models\PdsOtherInfo;
use App\Models\PdsPersonalInformation;
use App\Models\PdsQuestionnaire;
use App\Models\PdsReference;
use App\Models\PdsSnapshot;
use App\Models\PdsTraining;
use App\Models\PdsVoluntaryWork;
use App\Models\PdsWorkExperience;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class PdsSnapshotService
{
    private const ONE_RELATIONS = [
        'pdsPersonal' => PdsPersonalInformation::class,
        'pdsFamily' => PdsFamilyBackground::class,
        'pdsQuestionnaire' => PdsQuestionnaire::class,
        'pdsGovId' => PdsGovernmentId::class,
    ];

    private const MANY_RELATIONS = [
        'pdsChildren' => PdsChild::class,
        'pdsEducation' => PdsEducation::class,
        'pdsEligibilities' => PdsEligibility::class,
        'pdsWorkExperiences' => PdsWorkExperience::class,
        'pdsVoluntaryWorks' => PdsVoluntaryWork::class,
        'pdsTrainings' => PdsTraining::class,
        'pdsOthers' => PdsOtherInfo::class,
        'pdsReferences' => PdsReference::class,
    ];

    public function capture(Employee $employee, int $year): PdsSnapshot
    {
        $employee->load(array_merge(['user'], array_keys(self::ONE_RELATIONS), array_keys(self::MANY_RELATIONS)));

        $relations = [];
        foreach (self::ONE_RELATIONS as $relation => $modelClass) {
            $relations[$relation] = $employee->{$relation}?->getAttributes();
        }
        foreach (self::MANY_RELATIONS as $relation => $modelClass) {
            $relations[$relation] = $employee->{$relation}->map(fn ($record) => $record->getAttributes())->values()->all();
        }

        return PdsSnapshot::updateOrCreate(
            ['employee_id' => $employee->id, 'year' => $year],
            ['data' => ['employee' => $employee->getAttributes(), 'relations' => $relations]]
        );
    }

    public function employeeForYear(Employee $employee, int $year): Employee
    {
        if ($year === (int) now()->year) {
            return $this->loadLivePds($employee);
        }

        $snapshot = $employee->pdsSnapshots()->where('year', $year)->first();
        if (! $snapshot) {
            return $this->loadLivePds($employee);
        }

        $data = $snapshot->data;
        $archived = new Employee;
        $archived->setRawAttributes($data['employee'] ?? $employee->getAttributes(), true);
        $archived->exists = true;
        $archived->setRelation('user', $employee->user);

        foreach (self::ONE_RELATIONS as $relation => $modelClass) {
            $attributes = data_get($data, "relations.{$relation}");
            $archived->setRelation($relation, $attributes ? (new $modelClass)->newFromBuilder($attributes) : null);
        }

        foreach (self::MANY_RELATIONS as $relation => $modelClass) {
            $records = collect(data_get($data, "relations.{$relation}", []))
                ->map(fn (array $attributes) => (new $modelClass)->newFromBuilder($attributes));
            $archived->setRelation($relation, new EloquentCollection($records));
        }

        return $archived;
    }

    public function yearsFor(Employee $employee): Collection
    {
        return $employee->pdsSnapshots()
            ->pluck('year')
            ->push((int) now()->year)
            ->map(fn ($year) => (int) $year)
            ->unique()
            ->sortDesc()
            ->values();
    }

    public function hasYear(Employee $employee, int $year): bool
    {
        return $year === (int) now()->year || $employee->pdsSnapshots()->where('year', $year)->exists();
    }

    private function loadLivePds(Employee $employee): Employee
    {
        $employee->load(array_merge(['user'], array_keys(self::ONE_RELATIONS), array_keys(self::MANY_RELATIONS)));

        return $employee;
    }
}
