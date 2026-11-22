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
use App\Models\PdsSectionReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    private function dataFor(Employee $employee): array
    {
        $employee->loadMissing(array_merge(['user'], array_keys(self::ONE_RELATIONS), array_keys(self::MANY_RELATIONS)));

        $relations = [];
        foreach (self::ONE_RELATIONS as $relation => $modelClass) {
            $relations[$relation] = $employee->{$relation}?->getAttributes();
        }
        foreach (self::MANY_RELATIONS as $relation => $modelClass) {
            $relations[$relation] = $employee->{$relation}->map(fn ($record) => $record->getAttributes())->values()->all();
        }

        $attributes = $employee->getAttributes();
        unset($attributes['pds_approved_data']);
        return ['employee' => $attributes, 'relations' => $relations];
    }

    public function capture(Employee $employee, int $year): PdsSnapshot
    {
        return PdsSnapshot::updateOrCreate(
            ['employee_id' => $employee->id, 'year' => $year],
            ['data' => $this->dataFor($employee)]
        );
    }

    public function ensureApprovedBaseline(Employee $employee): void
    {
        if ($employee->pds_approved_data !== null) return;
        $data = $this->dataFor($employee);
        $employee->forceFill(['pds_approved_data' => $data])->save();
    }

    public function publishSections(Employee $employee, array $sections): void
    {
        $this->ensureApprovedBaseline($employee);
        $employee->unsetRelations();
        $data = $this->publishedData($employee, $sections);
        $employee->forceFill(['pds_approved_data' => $data])->save();
        $this->capture($this->employeeForYear($employee, (int) now()->year), (int) now()->year);
        if (in_array('Family Background', $sections, true)) app(SalnPdsDefaultsService::class)->syncApprovedFamily($employee, (int) now()->year);
    }

    private function publishedData(Employee $employee, array $sections): array
    {
        $data = $employee->pds_approved_data;
        $live = $this->dataFor($employee);
        $relationsBySection = [
            'Personal Information' => ['pdsPersonal'],
            'Family Background' => ['pdsFamily', 'pdsChildren'],
            'Educational Background' => ['pdsEducation'],
            'Civil Service Eligibility' => ['pdsEligibilities'],
            'Work Experience' => ['pdsWorkExperiences'],
            'Voluntary Work' => ['pdsVoluntaryWorks'],
            'Training' => ['pdsTrainings'],
            'Other Information' => ['pdsOthers'],
            'References' => ['pdsReferences', 'pdsQuestionnaire', 'pdsGovId'],
            'Questionnaire' => ['pdsQuestionnaire'],
        ];
        foreach ($sections as $section) {
            foreach ($relationsBySection[$section] ?? [] as $relation) {
                $data['relations'][$relation] = $live['relations'][$relation];
            }
        }
        if (in_array('Personal Information', $sections, true)) {
            $data['employee']['profile_picture'] = $live['employee']['profile_picture'] ?? null;
        }
        if (in_array('References', $sections, true)) {
            $data['employee']['e_signature_path'] = $live['employee']['e_signature_path'] ?? null;
        }
        return $data;
    }

    public function employeeForYear(Employee $employee, int $year, bool $approvedOnly = true): Employee
    {
        if ($this->isArchivedForYear($employee, $year)) return $this->fromData($employee, $this->blankData($employee));
        $liveYear = (int) ($employee->pds_record_year ?? now()->year);
        if ($year === (int) now()->year && $year === $liveYear) {
            $employee->refresh();
            return $approvedOnly && $employee->pds_approved_data !== null
                ? $this->fromData($employee, $employee->pds_approved_data)
                : $this->loadLivePds($employee);
        }

        $snapshot = $employee->pdsSnapshots()->where('year', $year)->first();
        if (! $snapshot) {
            if ($year === $liveYear) {
                return $approvedOnly && $employee->pds_approved_data !== null
                    ? $this->fromData($employee, $employee->pds_approved_data) : $this->loadLivePds($employee);
            }
            return $this->fromData($employee, $this->blankData($employee));
        }

        return $this->fromData($employee, !$approvedOnly ? ($snapshot->draft_data ?? $snapshot->data) : $snapshot->data);
    }

    private function blankData(Employee $employee): array
    {
        $attributes = $employee->getAttributes();
        unset($attributes['pds_approved_data']);
        $attributes['profile_picture'] = $attributes['e_signature_path'] = null;
        $relations = array_fill_keys(array_keys(self::ONE_RELATIONS), null);
        $relations['pdsPersonal'] = ['surname' => '', 'firstname' => '', 'middlename' => ''];
        foreach (self::MANY_RELATIONS as $relation => $model) $relations[$relation] = [];
        return ['employee' => $attributes, 'relations' => $relations];
    }

    public function draftForYear(Employee $employee, int $year): Employee
    {
        $draft = $this->employeeForYear($employee, $year, false);
        if ($this->isArchivedForYear($employee, $year)) {
            $draft->setRelation('pdsSectionReviews', new EloquentCollection);
            return $draft;
        }
        $snapshot = $employee->pdsSnapshots()->where('year', $year)->first();
        $useLive = $year === (int) ($employee->pds_record_year ?? now()->year) && ($year === (int) now()->year || !$snapshot);
        $reviews = $useLive ? $employee->pdsSectionReviews()->get()
            : new EloquentCollection($this->reviewData($employee->pdsSnapshots()->where('year', $year)->first()?->reviews ?? [])
                ->map(fn ($attributes) => new PdsSectionReview($attributes))->all());
        $draft->setRelation('pdsSectionReviews', $reviews);
        return $draft;
    }

    public function syncLiveDraft(Employee $employee, bool $submitted = false): void
    {
        $employee->unsetRelations();
        $attributes = [
            'data' => $employee->pds_approved_data ?? $this->dataFor($employee),
            'draft_data' => $this->dataFor($employee),
            'reviews' => $employee->pdsSectionReviews()->get()->map(fn ($review) => $review->attributesToArray())->all(),
        ];
        if ($submitted) $attributes['submitted_at'] = now();
        $employee->pdsSnapshots()->updateOrCreate(['year' => (int) now()->year], $attributes);
    }

    public function saveHistoricalDraft(Employee $employee, int $year, Request $request, array $changes): void
    {
        DB::transaction(function () use ($employee, $year, $request, $changes) {
            Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();
            $snapshot = $employee->pdsSnapshots()->firstOrNew(['year' => $year]);
            $snapshot->data ??= $this->blankData($employee);
            $data = $snapshot->draft_data ?? $snapshot->data;
            $existingTrainingPaths = collect($data['relations']['pdsTrainings'] ?? [])->pluck('attachment_path')->filter();
            foreach (['pdsPersonal' => 'personal', 'pdsFamily' => 'family', 'pdsQuestionnaire' => 'questionnaire', 'pdsGovId' => 'gov_id'] as $relation => $input) {
                $data['relations'][$relation] = array_merge($data['relations'][$relation] ?? [], $request->input($input, []));
            }
            foreach (['pdsChildren' => ['children', 'fullname'], 'pdsEducation' => ['education', 'school_name'], 'pdsEligibilities' => ['eligibility', 'title'], 'pdsWorkExperiences' => ['work_experience', 'position_title'], 'pdsVoluntaryWorks' => ['voluntary', 'organization_name'], 'pdsTrainings' => ['training', 'title'], 'pdsOthers' => ['others', 'description'], 'pdsReferences' => ['references', 'name']] as $relation => [$input, $key]) {
                $data['relations'][$relation] = collect($request->input($input, []))->filter(fn ($row) => filled($row[$key] ?? null))->values()->all();
            }
            if ($request->hasFile('pds_photo')) $data['employee']['profile_picture'] = $request->file('pds_photo')->store('profile_pictures', 'public_uploads');
            if ($request->hasFile('pds_signature')) {
                $path = $request->file('pds_signature')->store('employee-signatures', 'signatures');
                $data['relations']['pdsGovId']['signature_path'] = $path;
                $data['employee']['e_signature_path'] = $path;
            }
            foreach ($data['relations']['pdsTrainings'] as $i => &$row) {
                $existingPath = $row['existing_attachment_path'] ?? $row['attachment_path'] ?? null;
                if (!$request->hasFile("training.$i.attachment") && filled($existingPath)) abort_unless($existingTrainingPaths->containsStrict($existingPath), 403);
                $row['attachment_path'] = $request->hasFile("training.$i.attachment")
                    ? $request->file("training.$i.attachment")->store('pds-training-attachments', 'documents')
                    : $existingPath;
                unset($row['attachment'], $row['existing_attachment_path']);
            }
            unset($row);
            $reviews = $this->reviewData($snapshot->reviews ?? [])->keyBy('section_name');
            foreach ($changes as $section => $fields) {
                if (!$fields) continue;
                $old = $reviews->get($section, []);
                $reviews->put($section, ['section_name' => $section, 'status' => 'pending', 'remarks' => null,
                    'changed_fields' => array_values(array_unique(array_merge(in_array($old['status'] ?? '', ['pending', 'rejected']) ? ($old['changed_fields'] ?? []) : [], $fields))), 'reviewed_by_id' => null]);
            }
            $snapshot->draft_data = $data;
            $snapshot->reviews = $reviews->values()->all();
            $snapshot->submitted_at = now();
            $snapshot->save();
        });
    }

    public function reviewHistorical(Employee $employee, int $year, ?string $section, string $status, ?string $remarks): void
    {
        DB::transaction(function () use ($employee, $year, $section, $status, $remarks) {
            Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();
            $snapshot = $employee->pdsSnapshots()->where('year', $year)->lockForUpdate()->firstOrFail();
            $reviews = $this->reviewData($snapshot->reviews ?? []);
            $pending = $reviews->filter(fn ($review) => $review['status'] === 'pending' && (!$section || $review['section_name'] === $section));
            abort_if($pending->isEmpty(), 409, 'No pending update for this year and section.');
            if ($status === 'approved') {
                $draft = $this->fromData($employee, $snapshot->draft_data);
                $draft->pds_approved_data = $snapshot->data;
                $snapshot->data = $this->publishedData($draft, $pending->pluck('section_name')->all());
            }
            $snapshot->reviews = $reviews->map(function ($review) use ($pending, $status, $remarks) {
                if ($pending->contains(fn ($item) => $item['section_name'] === $review['section_name'])) {
                    $review['status'] = $status; $review['remarks'] = $remarks; $review['reviewed_by_id'] = auth()->id();
                    if ($status === 'approved') $review['changed_fields'] = [];
                }
                return $review;
            })->values()->all();
            $snapshot->save();
            if ($status === 'approved' && $pending->contains('section_name', 'Family Background')) app(SalnPdsDefaultsService::class)->syncApprovedFamily($employee, $year);
        });
    }

    private function fromData(Employee $employee, array $data): Employee
    {
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

    private function reviewData(array $reviews): Collection
    {
        return collect($reviews)->map(function ($review) {
            $review['changed_fields'] = is_string($review['changed_fields'] ?? null)
                ? (json_decode($review['changed_fields'], true) ?? []) : ($review['changed_fields'] ?? []);
            return $review;
        });
    }

    public function yearsFor(Employee $employee): Collection
    {
        return $employee->pdsSnapshots()
            ->pluck('year')
            ->push((int) now()->year)
            ->push((int) ($employee->pds_record_year ?? now()->year))
            ->merge(range((int) now()->year, min((int) now()->year, (int) ($employee->service_start_date?->year ?? now()->year))))
            ->map(fn ($year) => (int) $year)
            ->unique()
            ->sortDesc()
            ->values();
    }

    public function hasYear(Employee $employee, int $year): bool
    {
        return $this->yearsFor($employee)->containsStrict($year);
    }

    public function isArchivedForYear(Employee $employee, int $year): bool
    {
        return $employee->pdsSnapshots()->onlyTrashed()->where('year', $year)->exists();
    }

    public function archiveYear(Employee $employee, int $year): void
    {
        DB::transaction(function () use ($employee, $year) {
            Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();
            abort_if($this->isArchivedForYear($employee, $year), 409);
            $record = $employee->pdsSnapshots()->where('year', $year)->first();
            if (!$record) {
                abort_unless($year === (int) ($employee->pds_record_year ?? now()->year) && $employee->pdsPersonal()->exists(), 404);
                $draft = $this->draftForYear($employee, $year);
                $record = $this->capture($this->employeeForYear($employee, $year), $year);
                $record->update(['draft_data' => $this->dataFor($draft),
                    'reviews' => $draft->pdsSectionReviews->map(fn ($review) => $review->attributesToArray())->all()]);
            }
            $record->delete();
        });
    }

    private function loadLivePds(Employee $employee): Employee
    {
        $employee->load(array_merge(['user'], array_keys(self::ONE_RELATIONS), array_keys(self::MANY_RELATIONS)));

        return $employee;
    }
}
