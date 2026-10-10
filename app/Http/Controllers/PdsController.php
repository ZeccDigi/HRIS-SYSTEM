<?php

namespace App\Http\Controllers;

use App\Listeners\AuditLogListener;
use App\Models\Employee;
use App\Models\User;
use App\Models\UtilityOption;
use App\Notifications\PdsUpdatedNotification;
use App\Services\PdsExcelExporter;
use App\Services\PdsPdfExporter;
use App\Support\PdsFormatter;
use App\Support\UtilityOptionRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PdsController extends Controller
{
    public function index(): View
    {
        $employee = Auth::user()->employee;

        if (! $employee) {
            abort(403, 'User is not linked to an employee record.');
        }

        $employee->load([
            'pdsPersonal', 'pdsFamily', 'pdsChildren', 'pdsEducation',
            'pdsEligibilities', 'pdsWorkExperiences', 'pdsVoluntaryWorks',
            'pdsTrainings', 'pdsOthers', 'pdsQuestionnaire', 'pdsReferences', 'pdsGovId',
            'pdsSectionReviews',
        ]);

        return view('pds.index', compact('employee'));
    }

    public function download(Request $request, PdsPdfExporter $exporter): Response|RedirectResponse
    {
        $employee = Auth::user()->employee;

        if (! $employee) {
            abort(404);
        }

        try {
            return $exporter->download($employee, $request->boolean('print') ? 'inline' : 'attachment');
        } catch (\Throwable $e) {
            return redirect()
                ->route('pds.print')
                ->with('error', 'Could not generate the downloadable PDS PDF. You can still use Print / Save PDF from this preview. '.$e->getMessage());
        }
    }

    public function downloadExcel(PdsExcelExporter $exporter): BinaryFileResponse|RedirectResponse
    {
        $employee = Auth::user()->employee;

        if (! $employee) {
            abort(404);
        }

        try {
            return $exporter->download($employee);
        } catch (\Throwable $e) {
            return redirect()
                ->route('pds.index')
                ->with('error', 'Could not generate Excel PDS. '.$e->getMessage());
        }
    }

    public function print(Request $request): View|RedirectResponse
    {
        $employee = Auth::user()->employee;

        if (! $employee) {
            abort(404);
        }

        $employee->load([
            'user',
            'pdsPersonal', 'pdsFamily', 'pdsChildren', 'pdsEducation',
            'pdsEligibilities', 'pdsWorkExperiences', 'pdsVoluntaryWorks',
            'pdsTrainings', 'pdsOthers', 'pdsQuestionnaire', 'pdsReferences', 'pdsGovId',
        ]);

        return view('pds.print-2025', compact('employee'));
    }

    public function printClean(Request $request): View|RedirectResponse
    {
        $employee = Auth::user()->employee;

        if (! $employee) {
            abort(404);
        }

        $employee->load([
            'user',
            'pdsPersonal', 'pdsFamily', 'pdsChildren', 'pdsEducation',
            'pdsEligibilities', 'pdsWorkExperiences', 'pdsVoluntaryWorks',
            'pdsTrainings', 'pdsOthers', 'pdsQuestionnaire', 'pdsReferences', 'pdsGovId',
        ]);

        return view('pds.print-clean-2025', compact('employee'));
    }

    public function edit(): View
    {
        $employee = Auth::user()->employee;

        if (! $employee) {
            abort(404, 'Employee record not found for this user.');
        }

        $employee->load([
            'pdsPersonal', 'pdsFamily', 'pdsChildren', 'pdsEducation',
            'pdsEligibilities', 'pdsWorkExperiences', 'pdsVoluntaryWorks',
            'pdsTrainings', 'pdsOthers', 'pdsQuestionnaire', 'pdsReferences', 'pdsGovId',
            'pdsSectionReviews',
        ]);

        UtilityOptionRegistry::ensureDefaults();

        $utilityOptionSets = [
            'name_extensions' => UtilityOption::listFor('name_extensions', null, $employee->pdsPersonal?->name_extension),
            'civil_statuses' => UtilityOption::listFor('civil_statuses', null, $employee->pdsPersonal?->civil_status),
            'blood_types' => UtilityOption::listFor('blood_types', null, $employee->pdsPersonal?->blood_type),
            'citizenship_types' => UtilityOption::listFor('citizenship_types', null, $employee->pdsPersonal?->citizenship_type),
            'countries' => UtilityOption::listFor('countries', null, $employee->pdsPersonal?->citizenship_country),
            'education_levels' => UtilityOption::listFor('education_levels'),
            'highest_levels' => UtilityOption::listFor('highest_levels'),
            'eligibility_titles' => UtilityOption::listFor('eligibility_titles'),
            'salary_grades' => UtilityOption::listFor('salary_grades'),
            'step_increments' => UtilityOption::listFor('step_increments'),
            'appointment_statuses' => UtilityOption::listFor('appointment_statuses'),
            'government_service_options' => UtilityOption::listFor('government_service_options'),
            'government_id_types' => UtilityOption::listFor('government_id_types', null, $employee->pdsGovId?->id_type),
        ];

        $locationOptionSets = [
            'countries' => UtilityOption::listFor('countries', null, $employee->pdsPersonal?->citizenship_country)->values(),
            'ph_regions' => UtilityOption::query()->active()->forGroup('ph_regions')->orderBy('sort_order')->orderBy('label')->get(['label', 'value', 'parent_value']),
            'ph_provinces' => UtilityOption::query()->active()->forGroup('ph_provinces')->orderBy('sort_order')->orderBy('label')->get(['label', 'value', 'parent_value']),
            'ph_cities' => UtilityOption::query()->active()->forGroup('ph_cities')->orderBy('sort_order')->orderBy('label')->get(['label', 'value', 'parent_value']),
            'ph_barangays' => UtilityOption::query()->active()->forGroup('ph_barangays')->orderBy('sort_order')->orderBy('label')->get(['label', 'value', 'parent_value']),
        ];

        $cityZipCodes = $this->cityZipCodes();

        return view('pds.edit', compact('employee', 'utilityOptionSets', 'locationOptionSets', 'cityZipCodes'));
    }

    public function showForHr(Employee $employee): View
    {
        $employee->load([
            'user',
            'pdsPersonal', 'pdsFamily', 'pdsChildren', 'pdsEducation',
            'pdsEligibilities', 'pdsWorkExperiences', 'pdsVoluntaryWorks',
            'pdsTrainings', 'pdsOthers', 'pdsQuestionnaire', 'pdsReferences', 'pdsGovId',
            'pdsSectionReviews',
        ]);

        UtilityOptionRegistry::ensureDefaults();

        $utilityOptionSets = [
            'name_extensions' => UtilityOption::listFor('name_extensions', null, $employee->pdsPersonal?->name_extension),
            'civil_statuses' => UtilityOption::listFor('civil_statuses', null, $employee->pdsPersonal?->civil_status),
            'blood_types' => UtilityOption::listFor('blood_types', null, $employee->pdsPersonal?->blood_type),
            'citizenship_types' => UtilityOption::listFor('citizenship_types', null, $employee->pdsPersonal?->citizenship_type),
            'countries' => UtilityOption::listFor('countries', null, $employee->pdsPersonal?->citizenship_country),
            'education_levels' => UtilityOption::listFor('education_levels'),
            'highest_levels' => UtilityOption::listFor('highest_levels'),
            'eligibility_titles' => UtilityOption::listFor('eligibility_titles'),
            'salary_grades' => UtilityOption::listFor('salary_grades'),
            'step_increments' => UtilityOption::listFor('step_increments'),
            'appointment_statuses' => UtilityOption::listFor('appointment_statuses'),
            'government_service_options' => UtilityOption::listFor('government_service_options'),
            'government_id_types' => UtilityOption::listFor('government_id_types', null, $employee->pdsGovId?->id_type),
        ];

        $locationOptionSets = [
            'countries' => UtilityOption::listFor('countries', null, $employee->pdsPersonal?->citizenship_country)->values(),
            'ph_regions' => UtilityOption::query()->active()->forGroup('ph_regions')->orderBy('sort_order')->orderBy('label')->get(['label', 'value', 'parent_value']),
            'ph_provinces' => UtilityOption::query()->active()->forGroup('ph_provinces')->orderBy('sort_order')->orderBy('label')->get(['label', 'value', 'parent_value']),
            'ph_cities' => UtilityOption::query()->active()->forGroup('ph_cities')->orderBy('sort_order')->orderBy('label')->get(['label', 'value', 'parent_value']),
            'ph_barangays' => UtilityOption::query()->active()->forGroup('ph_barangays')->orderBy('sort_order')->orderBy('label')->get(['label', 'value', 'parent_value']),
        ];

        $cityZipCodes = $this->cityZipCodes();
        $isReadOnly = true;
        $pdsReviewUpdates = $this->pdsReviewUpdates($employee);
        $pdsReviewChangedInputNames = $this->pdsReviewChangedInputNames($employee);

        return view('pds.edit', compact('employee', 'utilityOptionSets', 'locationOptionSets', 'cityZipCodes', 'isReadOnly', 'pdsReviewUpdates', 'pdsReviewChangedInputNames'));
    }

    public function update(Request $request): RedirectResponse
    {
        $employee = Auth::user()->employee;

        if (! $employee) {
            abort(404);
        }

        $personal = $request->input('personal', []);
        if (array_key_exists('height_m', $personal)) {
            $personal['height_m'] = PdsFormatter::heightCm($personal['height_m'], '');
            $request->merge(['personal' => $personal]);
        }

        if (! empty($personal['mobile_no'])) {
            $digits = substr(preg_replace('/\D/', '', (string) $personal['mobile_no']), 0, 11);
            $personal['mobile_no'] = strlen($digits) === 11
                ? substr($digits, 0, 4).'-'.substr($digits, 4, 3).'-'.substr($digits, 7, 4)
                : $personal['mobile_no'];
            $request->merge(['personal' => $personal]);
        }

        $request->validate([
            'personal.surname' => 'nullable|string',
            'personal.firstname' => 'nullable|string',
            'personal.middlename' => 'nullable|string',
            'personal.name_extension' => 'nullable|string',
            'personal.date_of_birth' => 'nullable|date',
            'personal.place_of_birth' => 'nullable|string',
            'personal.sex' => 'nullable|string',
            'personal.civil_status' => 'nullable|string',
            'personal.height_m' => 'nullable|string',
            'personal.weight_kg' => 'nullable|string',
            'personal.blood_type' => 'nullable|string',
            'personal.gsis_id_no' => 'nullable|string',
            'personal.pagibig_id_no' => 'nullable|string',
            'personal.philhealth_no' => 'nullable|string',
            'personal.sss_no' => 'nullable|string',
            'personal.tin_no' => 'nullable|string',
            'personal.agency_employee_no' => 'nullable|string',
            'personal.umid_no' => 'nullable|string',
            'personal.philsys_no' => 'nullable|string',
            'personal.citizenship' => 'nullable|string',
            'personal.citizenship_type' => 'nullable|string',
            'personal.citizenship_country' => 'nullable|string',
            'personal.res_house_no' => 'nullable|string',
            'personal.res_street' => 'nullable|string',
            'personal.res_subdivision' => 'nullable|string',
            'personal.res_region' => 'nullable|string',
            'personal.res_barangay' => 'nullable|string',
            'personal.res_city' => 'nullable|string',
            'personal.res_province' => 'nullable|string',
            'personal.res_zip_code' => 'nullable|string',
            'personal.perm_house_no' => 'nullable|string',
            'personal.perm_street' => 'nullable|string',
            'personal.perm_subdivision' => 'nullable|string',
            'personal.perm_region' => 'nullable|string',
            'personal.perm_barangay' => 'nullable|string',
            'personal.perm_city' => 'nullable|string',
            'personal.perm_province' => 'nullable|string',
            'personal.perm_zip_code' => 'nullable|string',
            'personal.telephone_no' => 'nullable|string',
            'personal.mobile_no' => ['nullable', 'regex:/^09\d{2}-\d{3}-\d{4}$/'],
            'personal.email_address' => 'nullable|email',
            'family.spouse_surname' => 'nullable|string',
            'family.spouse_firstname' => 'nullable|string',
            'family.spouse_extension' => 'nullable|string',
            'family.father_surname' => 'nullable|string',
            'family.mother_maiden_surname' => 'nullable|string',
            'children.*.fullname' => 'nullable|string',
            'children.*.date_of_birth' => 'nullable|date',
            'education.*.level' => 'required_with:education.*.school_name|nullable|string',
            'education.*.school_name' => 'nullable|string',
            'eligibility.*.title' => 'nullable|string',
            'work_experience.*.position_title' => 'nullable|string',
            'work_experience.*.company' => 'nullable|string',
            'training.*.title' => 'nullable|string',
            'training.*.attachment' => 'nullable|file|mimes:pdf|max:5120',
            'others.*.type' => 'nullable|in:Skill,Distinction,Membership',
            'others.*.description' => 'nullable|string',
            'references.*.name' => 'nullable|string',
            'references.*.telephone_no' => 'nullable|string',
            'questionnaire' => 'nullable|array',
            'gov_id.id_type' => 'nullable|string',
            'gov_id.id_no' => 'nullable|string',
            'gov_id.date_place_issuance' => 'nullable|string',
            'pds_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'pds_signature' => 'nullable|image|mimes:png|max:2048',
        ], [
            'education.*.level.required_with' => 'The Level field (e.g., Elementary, Secondary, College) is required when a School Name is provided in Educational Background.',
        ]);

        $employee->load([
            'pdsPersonal', 'pdsFamily', 'pdsChildren', 'pdsEducation',
            'pdsEligibilities', 'pdsWorkExperiences', 'pdsVoluntaryWorks',
            'pdsTrainings', 'pdsOthers', 'pdsQuestionnaire', 'pdsReferences', 'pdsGovId',
            'pdsSectionReviews',
        ]);

        $changedFieldsBySection = $this->changedFieldsBySection($employee, $request);

        DB::transaction(function () use ($employee, $request, $changedFieldsBySection) {
            $employee->pdsPersonal()->updateOrCreate(['employee_id' => $employee->id], $request->input('personal', []));
            $employee->pdsFamily()->updateOrCreate(['employee_id' => $employee->id], $request->input('family', []));
            $employee->pdsQuestionnaire()->updateOrCreate(['employee_id' => $employee->id], $request->input('questionnaire', []));

            if ($request->hasFile('pds_photo')) {
                if ($employee->profile_picture) {
                    Storage::disk('public_uploads')->delete($employee->profile_picture);
                    Storage::disk('public')->delete($employee->profile_picture);
                }

                $employee->update([
                    'profile_picture' => $request->file('pds_photo')->store('profile_pictures', 'public_uploads'),
                ]);
            }

            $govIdData = $request->input('gov_id', []);
            if ($request->hasFile('pds_signature')) {
                $existingSignature = $employee->pdsGovId?->signature_path;
                if ($existingSignature && $existingSignature !== $employee->e_signature_path) {
                    Storage::disk('public')->delete($existingSignature);
                }
                if ($employee->e_signature_path) {
                    Storage::disk('public')->delete($employee->e_signature_path);
                }

                $signaturePath = $request->file('pds_signature')->store('employee-signatures', 'public');
                $govIdData['signature_path'] = $signaturePath;
                $employee->update(['e_signature_path' => $signaturePath]);
            }

            $employee->pdsGovId()->updateOrCreate(['employee_id' => $employee->id], $govIdData);

            $employee->pdsChildren()->delete();
            foreach ($request->input('children', []) as $child) {
                if (! empty($child['fullname'])) {
                    $employee->pdsChildren()->create($child);
                }
            }

            $employee->pdsEducation()->delete();
            foreach ($request->input('education', []) as $edu) {
                if (! empty($edu['school_name']) && ! empty($edu['level'])) {
                    $employee->pdsEducation()->create($edu);
                }
            }

            $employee->pdsEligibilities()->delete();
            foreach ($request->input('eligibility', []) as $eli) {
                if (! empty($eli['title'])) {
                    $employee->pdsEligibilities()->create($eli);
                }
            }

            $employee->pdsWorkExperiences()->delete();
            foreach ($request->input('work_experience', []) as $work) {
                if (! empty($work['position_title'])) {
                    $employee->pdsWorkExperiences()->create($work);
                }
            }

            $employee->pdsTrainings()->delete();
            foreach ($request->input('training', []) as $index => $train) {
                if (! empty($train['title'])) {
                    if ($request->hasFile("training.$index.attachment")) {
                        if (! empty($train['existing_attachment_path'])) {
                            Storage::disk('public')->delete($train['existing_attachment_path']);
                        }

                        $train['attachment_path'] = $request->file("training.$index.attachment")->store('pds-training-attachments', 'public');
                    } elseif (! empty($train['existing_attachment_path'])) {
                        $train['attachment_path'] = $train['existing_attachment_path'];
                    }

                    unset($train['attachment'], $train['existing_attachment_path']);

                    $employee->pdsTrainings()->create($train);
                }
            }

            $employee->pdsVoluntaryWorks()->delete();
            foreach ($request->input('voluntary', []) as $vol) {
                if (! empty($vol['organization_name'])) {
                    $employee->pdsVoluntaryWorks()->create($vol);
                }
            }

            $employee->pdsOthers()->delete();
            foreach ($request->input('others', []) as $other) {
                if (! empty($other['description'])) {
                    $employee->pdsOthers()->create($other);
                }
            }

            $employee->pdsReferences()->delete();
            foreach ($request->input('references', []) as $ref) {
                if (! empty($ref['name'])) {
                    $employee->pdsReferences()->create($ref);
                }
            }

            foreach ($changedFieldsBySection as $sectionName => $changedFields) {
                $this->markPdsSectionChanged($employee, $sectionName, $changedFields);
            }
        });

        AuditLogListener::logActivity(
            'Update PDS',
            'Employees',
            'Updated Personal Data Sheet (PDS) for ' . trim(($employee->firstname ?? '') . ' ' . ($employee->lastname ?? '')),
            Auth::user()
        );

        // Notify HR users (ADMIN / HRSTAFF) about the PDS update
        $hrUsers = User::whereHas('employee', function ($q) {
            $q->whereIn('account_role', ['ADMIN', 'HRSTAFF', 'admin', 'hrstaff', 'hr staff']);
        })->get();

        $employeeName = trim(($employee->firstname ?? '') . ' ' . ($employee->lastname ?? ''));
        foreach ($hrUsers as $hrUser) {
            if ($hrUser->id !== Auth::id()) {
                $hrUser->notify(new PdsUpdatedNotification(
                    employee: $employee,
                    title: 'PDS Updated',
                    message: "{$employeeName} updated their Personal Data Sheet."
                ));
            }
        }

        return redirect()->route('pds.edit')->with('success', 'Personal Data Sheet successfully updated.');
    }

    private function pdsReviewUpdates(Employee $employee): array
    {
        $reviews = $employee->pdsSectionReviews->keyBy('section_name');

        return [
            'personal' => $this->labelsForChangedFields($reviews->get('Personal Information')?->changed_fields ?? [], $this->personalFieldLabels(), null, false),
            'family' => array_merge(
                $this->labelsForChangedFields($reviews->get('Family Background')?->changed_fields ?? [], $this->familyFieldLabels(), null, false),
                $this->labelsForChangedFields($reviews->get('Family Background')?->changed_fields ?? [], $this->childFieldLabels(), 'children.')
            ),
            'education' => $this->labelsForChangedFields($reviews->get('Educational Background')?->changed_fields ?? [], $this->educationFieldLabels()),
            'eligibility' => $this->labelsForChangedFields($reviews->get('Civil Service Eligibility')?->changed_fields ?? [], $this->eligibilityFieldLabels()),
            'work' => $this->labelsForChangedFields($reviews->get('Work Experience')?->changed_fields ?? [], $this->workFieldLabels()),
            'voluntary' => $this->labelsForChangedFields($reviews->get('Voluntary Work')?->changed_fields ?? [], $this->voluntaryFieldLabels()),
            'training' => $this->labelsForChangedFields($reviews->get('Training')?->changed_fields ?? [], $this->trainingFieldLabels()),
            'otherinfo' => array_merge(
                $this->labelsForChangedFields($reviews->get('Other Information')?->changed_fields ?? [], $this->otherFieldLabels()),
                $this->labelsForChangedFields($reviews->get('References')?->changed_fields ?? [], $this->referenceFieldLabels(), 'references.')
            ),
            'declaration' => array_merge(
                $this->labelsForChangedFields($reviews->get('References')?->changed_fields ?? [], $this->questionnaireFieldLabels(), 'questionnaire.'),
                $this->labelsForChangedFields($reviews->get('References')?->changed_fields ?? [], $this->governmentIdFieldLabels(), 'gov_id.')
            ),
        ];
    }

    private function changedFieldsBySection(Employee $employee, Request $request): array
    {
        return [
            'Personal Information' => array_merge(
                $this->changedKeysFromInput($employee->pdsPersonal, $request->input('personal', []), array_keys($this->personalFieldLabels())),
                $request->hasFile('pds_photo') ? ['pds_photo'] : []
            ),
            'Family Background' => array_merge(
                $this->changedKeysFromInput($employee->pdsFamily, $request->input('family', []), array_keys($this->familyFieldLabels())),
                $this->changedKeysFromRows($employee->pdsChildren, $request->input('children', []), array_keys($this->childFieldLabels()), 'children')
            ),
            'Educational Background' => $this->changedKeysFromRows($employee->pdsEducation, $request->input('education', []), array_keys($this->educationFieldLabels())),
            'Civil Service Eligibility' => $this->changedKeysFromRows($employee->pdsEligibilities, $request->input('eligibility', []), array_keys($this->eligibilityFieldLabels())),
            'Work Experience' => $this->changedKeysFromRows($employee->pdsWorkExperiences, $request->input('work_experience', []), array_keys($this->workFieldLabels())),
            'Voluntary Work' => $this->changedKeysFromRows($employee->pdsVoluntaryWorks, $request->input('voluntary', []), array_keys($this->voluntaryFieldLabels())),
            'Training' => $this->changedKeysFromRows($employee->pdsTrainings, $request->input('training', []), array_keys($this->trainingFieldLabels())),
            'Other Information' => $this->changedKeysFromRows($employee->pdsOthers, $request->input('others', []), array_keys($this->otherFieldLabels())),
            'References' => array_merge(
                $this->changedKeysFromRows($employee->pdsReferences, $request->input('references', []), array_keys($this->referenceFieldLabels()), 'references'),
                $this->changedKeysFromInput($employee->pdsQuestionnaire, $request->input('questionnaire', []), array_keys($this->questionnaireFieldLabels()), 'questionnaire'),
                $this->changedKeysFromInput($employee->pdsGovId, $request->input('gov_id', []), array_keys($this->governmentIdFieldLabels()), 'gov_id'),
                $request->hasFile('pds_signature') ? ['gov_id.signature_path'] : []
            ),
        ];
    }

    private function changedKeysFromInput($record, array $input, array $fields, ?string $prefix = null): array
    {
        $changed = [];

        foreach ($fields as $field) {
            if (! array_key_exists($field, $input)) {
                continue;
            }

            if ($this->normalizePdsValue($record?->{$field} ?? null) !== $this->normalizePdsValue($input[$field] ?? null)) {
                $changed[] = $prefix ? "{$prefix}.{$field}" : $field;
            }
        }

        return $changed;
    }

    private function changedKeysFromRows($rows, array $inputRows, array $fields, ?string $prefix = null): array
    {
        $changed = [];
        $existingRows = $rows->values();
        $inputRows = array_values($inputRows);
        $maxRows = max($existingRows->count(), count($inputRows));

        for ($index = 0; $index < $maxRows; $index++) {
            $record = $existingRows->get($index);
            $input = $inputRows[$index] ?? [];

            foreach ($fields as $field) {
                $oldValue = $record?->{$field} ?? null;
                $newValue = $input[$field] ?? null;

                if ($this->normalizePdsValue($oldValue) !== $this->normalizePdsValue($newValue)) {
                    $changed[] = $prefix ? "{$prefix}.{$index}.{$field}" : "{$index}.{$field}";
                }
            }
        }

        return $changed;
    }

    private function normalizePdsValue($value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return trim((string) ($value ?? ''));
    }

    private function markPdsSectionChanged(Employee $employee, string $sectionName, array $changedFields): void
    {
        $changedFields = array_values(array_unique(array_filter($changedFields)));

        if (empty($changedFields)) {
            return;
        }

        $review = $employee->pdsSectionReviews()->firstOrNew(['section_name' => $sectionName]);
        $existingFields = in_array($review->status, ['pending', 'rejected'], true) ? ($review->changed_fields ?? []) : [];

        $review->status = 'pending';
        $review->changed_fields = array_values(array_unique(array_merge($existingFields, $changedFields)));
        $review->reviewed_by_id = null;
        $review->save();
    }

    private function labelsForChangedFields(array $changedFields, array $fieldLabels, ?string $prefix = null, bool $includeDottedFields = true): array
    {
        return collect($changedFields)
            ->filter(function ($field) use ($prefix, $includeDottedFields) {
                if ($prefix) {
                    return str_starts_with($field, $prefix);
                }

                return $includeDottedFields || ! str_contains($field, '.');
            })
            ->map(function ($field) use ($fieldLabels, $prefix) {
                $field = $prefix ? substr($field, strlen($prefix)) : $field;
                $baseField = str_contains($field, '.') ? substr($field, strrpos($field, '.') + 1) : $field;

                return $fieldLabels[$baseField] ?? $this->humanizeChangedField($baseField);
            })
            ->unique()
            ->values()
            ->all();
    }

    private function pdsReviewChangedInputNames(Employee $employee): array
    {
        $reviews = $employee->pdsSectionReviews->keyBy('section_name');
        $inputNames = [];

        foreach ($reviews->get('Personal Information')?->changed_fields ?? [] as $field) {
            if ($field === 'pds_photo') {
                $inputNames[] = 'pds_photo';
                continue;
            }

            $inputNames[] = "personal[{$field}]";
        }

        foreach ($reviews->get('Family Background')?->changed_fields ?? [] as $field) {
            if (str_starts_with($field, 'children.')) {
                $inputNames[] = $this->inputNameFromDottedField($field, 'children');
                continue;
            }

            $inputNames[] = "family[{$field}]";
        }

        $rowSectionMap = [
            'Educational Background' => 'education',
            'Civil Service Eligibility' => 'eligibility',
            'Work Experience' => 'work_experience',
            'Voluntary Work' => 'voluntary',
            'Training' => 'training',
            'Other Information' => 'others',
        ];

        foreach ($rowSectionMap as $sectionName => $inputPrefix) {
            foreach ($reviews->get($sectionName)?->changed_fields ?? [] as $field) {
                $inputNames[] = $this->inputNameFromDottedField($field, $inputPrefix);
            }
        }

        foreach ($reviews->get('References')?->changed_fields ?? [] as $field) {
            if (str_starts_with($field, 'references.')) {
                $inputNames[] = $this->inputNameFromDottedField($field, 'references');
            } elseif (str_starts_with($field, 'questionnaire.')) {
                $inputNames[] = 'questionnaire['.substr($field, strlen('questionnaire.')).']';
            } elseif (str_starts_with($field, 'gov_id.')) {
                $inputNames[] = 'gov_id['.substr($field, strlen('gov_id.')).']';
            }
        }

        return array_values(array_unique(array_filter($inputNames)));
    }

    private function inputNameFromDottedField(string $field, string $inputPrefix): ?string
    {
        $field = str_starts_with($field, "{$inputPrefix}.") ? substr($field, strlen($inputPrefix) + 1) : $field;
        $parts = explode('.', $field);

        if (count($parts) < 2) {
            return null;
        }

        return "{$inputPrefix}[{$parts[0]}][{$parts[1]}]";
    }

    private function humanizeChangedField(string $field): string
    {
        return str($field)->replace('_', ' ')->title()->toString();
    }

    private function personalFieldLabels(): array
    {
        return [
            'surname' => 'Surname',
            'firstname' => 'First name',
            'middlename' => 'Middle name',
            'name_extension' => 'Name extension',
            'date_of_birth' => 'Date of birth',
            'place_of_birth' => 'Place of birth',
            'sex' => 'Sex',
            'civil_status' => 'Civil status',
            'height_m' => 'Height',
            'weight_kg' => 'Weight',
            'blood_type' => 'Blood type',
            'gsis_id_no' => 'GSIS ID no.',
            'pagibig_id_no' => 'Pag-IBIG ID no.',
            'philhealth_no' => 'PhilHealth no.',
            'sss_no' => 'SSS no.',
            'tin_no' => 'TIN no.',
            'agency_employee_no' => 'Agency employee no.',
            'umid_no' => 'UMID ID no.',
            'philsys_no' => 'PhilSys no.',
            'citizenship' => 'Citizenship',
            'citizenship_type' => 'Citizenship type',
            'citizenship_country' => 'Citizenship country',
            'res_house_no' => 'Residential house/block/lot no.',
            'res_street' => 'Residential street',
            'res_subdivision' => 'Residential subdivision/village',
            'res_region' => 'Residential region',
            'res_province' => 'Residential province',
            'res_city' => 'Residential city/municipality',
            'res_barangay' => 'Residential barangay',
            'res_zip_code' => 'Residential ZIP code',
            'perm_house_no' => 'Permanent house/block/lot no.',
            'perm_street' => 'Permanent street',
            'perm_subdivision' => 'Permanent subdivision/village',
            'perm_region' => 'Permanent region',
            'perm_province' => 'Permanent province',
            'perm_city' => 'Permanent city/municipality',
            'perm_barangay' => 'Permanent barangay',
            'perm_zip_code' => 'Permanent ZIP code',
            'telephone_no' => 'Telephone no.',
            'mobile_no' => 'Mobile no.',
            'email_address' => 'Email address',
            'pds_photo' => 'Official photo',
        ];
    }

    private function familyFieldLabels(): array
    {
        return [
            'spouse_surname' => 'Spouse surname',
            'spouse_firstname' => 'Spouse first name',
            'spouse_middlename' => 'Spouse middle name',
            'spouse_extension' => 'Spouse extension',
            'spouse_occupation' => 'Spouse occupation',
            'spouse_employer' => 'Spouse employer',
            'spouse_business_address' => 'Spouse business address',
            'spouse_telephone_no' => 'Spouse telephone no.',
            'father_surname' => 'Father surname',
            'father_firstname' => 'Father first name',
            'father_middlename' => 'Father middle name',
            'father_extension' => 'Father extension',
            'mother_maiden_surname' => 'Mother maiden surname',
            'mother_firstname' => 'Mother first name',
            'mother_middlename' => 'Mother middle name',
        ];
    }

    private function childFieldLabels(): array
    {
        return ['fullname' => 'Child name', 'date_of_birth' => 'Child date of birth'];
    }

    private function educationFieldLabels(): array
    {
        return ['level' => 'Education level', 'school_name' => 'School name', 'course' => 'Course', 'period_from' => 'Education period from', 'period_to' => 'Education period to', 'highest_level' => 'Highest level', 'year_graduated' => 'Year graduated', 'honors' => 'Honors'];
    }

    private function eligibilityFieldLabels(): array
    {
        return ['title' => 'Eligibility title', 'rating' => 'Rating', 'date_of_exam' => 'Date of examination', 'place_of_exam' => 'Place of examination', 'license_number' => 'License number', 'license_validity' => 'License validity'];
    }

    private function workFieldLabels(): array
    {
        return ['date_from' => 'Work date from', 'date_to' => 'Work date to', 'position_title' => 'Position title', 'company' => 'Department/agency/company', 'monthly_salary' => 'Monthly salary', 'salary_grade' => 'Salary grade', 'appointment_status' => 'Appointment status', 'is_gov_service' => 'Government service'];
    }

    private function voluntaryFieldLabels(): array
    {
        return ['organization_name' => 'Organization name', 'date_from' => 'Voluntary work date from', 'date_to' => 'Voluntary work date to', 'number_of_hours' => 'Number of hours', 'position' => 'Position/nature of work'];
    }

    private function trainingFieldLabels(): array
    {
        return ['title' => 'Training title', 'date_from' => 'Training date from', 'date_to' => 'Training date to', 'number_of_hours' => 'Number of hours', 'type' => 'Training type', 'conducted_by' => 'Conducted/sponsored by', 'attachment_path' => 'Training attachment'];
    }

    private function otherFieldLabels(): array
    {
        return ['type' => 'Other information type', 'description' => 'Other information details'];
    }

    private function referenceFieldLabels(): array
    {
        return ['name' => 'Reference name', 'address' => 'Reference address', 'telephone_no' => 'Reference telephone no.'];
    }

    private function questionnaireFieldLabels(): array
    {
        return ['q34_a' => 'Question 34-A', 'q34_b' => 'Question 34-B', 'q34_details' => 'Question 34 details', 'q35_a' => 'Question 35-A', 'q35_b' => 'Question 35-B', 'q35_details' => 'Question 35 details', 'q35_date_filed' => 'Question 35 date filed', 'q35_status' => 'Question 35 status', 'q36' => 'Question 36', 'q36_details' => 'Question 36 details', 'q37' => 'Question 37', 'q37_details' => 'Question 37 details', 'q38_a' => 'Question 38-A', 'q38_a_details' => 'Question 38-A details', 'q38_b' => 'Question 38-B', 'q38_b_details' => 'Question 38-B details', 'q39' => 'Question 39', 'q39_details' => 'Question 39 details', 'q40_a' => 'Question 40-A', 'q40_a_details' => 'Question 40-A details', 'q40_b' => 'Question 40-B', 'q40_b_details' => 'Question 40-B details', 'q40_c' => 'Question 40-C', 'q40_c_details' => 'Question 40-C details'];
    }

    private function governmentIdFieldLabels(): array
    {
        return ['id_type' => 'Government ID type', 'id_no' => 'Government ID number', 'date_place_issuance' => 'ID date/place of issuance', 'signature_path' => 'Signature'];
    }

    private function cityZipCodes(): array
    {
        return [
            'Davao De Oro|Compostela' => '6003',
            'Davao De Oro|Mabini' => '4202',
            'Davao De Oro|Maco' => '8114',
            'Davao De Oro|Mawab' => '8108',
            'Davao De Oro|Monkayo' => '8111',
            'Davao De Oro|Montevista' => '8107',
            'Davao De Oro|Nabunturan' => '8800',
            'Davao De Oro|New Bataan' => '8110',
            'Davao De Oro|Pantukan' => '8117',
            'Davao Del Norte|Asuncion' => '8102',
            'Davao Del Norte|Carmen' => '8101',
            'Davao Del Norte|City of Panabo' => '8105',
            'Davao Del Norte|City of Tagum' => '8100',
            'Davao Del Norte|Island Garden City of Samal' => '8119',
            'Davao Del Norte|Kapalong' => '8113',
            'Davao Del Norte|New Corella' => '8104',
            'Davao Del Norte|San Isidro' => '2809',
            'Davao Del Norte|Santo Tomas' => '8112',
            'Davao Del Sur|Bansalan' => '8005',
            'Davao Del Sur|City of Davao' => '8000',
            'Davao Del Sur|City of Digos' => '8002',
            'Davao Del Sur|Hagonoy' => '8006',
            'Davao Del Sur|Kiblawan' => '8008',
            'Davao Del Sur|Magsaysay' => '8004',
            'Davao Del Sur|Malalag' => '8010',
            'Davao Del Sur|Matanao' => '8003',
            'Davao Del Sur|Padada' => '8007',
            'Davao Del Sur|Santa Cruz' => '8001',
            'Davao Del Sur|Sulop' => '8009',
            'Davao Occidental|Don Marcelino' => '8013',
            'Davao Occidental|Jose Abad Santos' => '8014',
            'Davao Occidental|Malita' => '8012',
            'Davao Occidental|Santa Maria' => '3022',
            'Davao Occidental|Sarangani' => '8015',
            'Davao Oriental|Baganga' => '8204',
            'Davao Oriental|Banaybanay' => '8208',
            'Davao Oriental|Boston' => '8206',
            'Davao Oriental|Caraga' => '8203',
            'Davao Oriental|Cateel' => '8205',
            'Davao Oriental|City of Mati' => '8200',
            'Davao Oriental|Lupon' => '8207',
            'Davao Oriental|Manay' => '8202',
            'Davao Oriental|San Isidro' => '8209',
            'Davao Oriental|Tarragona' => '8201',
        ];
    }
}
