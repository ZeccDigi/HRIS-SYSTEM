<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreSalnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->employee !== null;
    }

    protected function prepareForValidation(): void
    {
        foreach (['real_properties' => ['assessed_value', 'fair_market_value', 'acquisition_cost'], 'personal_properties' => ['acquisition_cost'], 'liabilities' => ['outstanding_balance']] as $group => $fields) {
            $rows = $this->input($group, []);
            if (!is_array($rows)) continue;
            foreach ($rows as &$row) {
                if (!is_array($row)) continue;
                foreach ($fields as $field) if (isset($row[$field]) && is_string($row[$field])) $row[$field] = str_replace(',', '', $row[$field]);
            }
            unset($row);
            $this->merge([$group => $rows]);
        }
        $employee = $this->user()?->employee;
        if ($employee && $this->filled('as_of_date')) {
            try { $year = \Carbon\Carbon::parse($this->input('as_of_date'))->year; } catch (\Throwable) { $year = null; }
            if ($year) {
                $service = app(\App\Services\SalnPdsDefaultsService::class);
                foreach ($service->defaults($employee, $year) as $key => $defaults) {
                    if (is_array($this->input($key, []))) $this->merge([$key => $service->mergeLocked($this->input($key, []), $defaults, $key)]);
                }
                if (is_array($this->input('children', []))) $this->merge(['children' => $service->children($employee, $year, $this->input('as_of_date'), $this->input('children', []))]);
            }
        }
        $this->merge([
            'has_business_interests' => $this->boolean('has_business_interests'),
            'has_relatives_in_gov' => $this->boolean('has_relatives_in_gov'),
            'children' => $this->filterRows($this->input('children', []), ['name']),
            'real_properties' => $this->filterRows($this->input('real_properties', []), ['description']),
            'personal_properties' => $this->filterRows($this->input('personal_properties', []), ['description']),
            'liabilities' => $this->filterRows($this->input('liabilities', []), ['nature', 'creditor']),
            'business_interests' => $this->boolean('has_business_interests')
                ? $this->filterRows($this->input('business_interests', []), ['name'])
                : [],
            'relatives_in_gov' => $this->boolean('has_relatives_in_gov')
                ? $this->filterRows($this->input('relatives_in_gov', []), ['name'])
                : [],
        ]);

        if ($this->boolean('declarant_info.multiple_marriages_not_applicable')) {
            $declarant = $this->input('declarant_info', []);
            $declarant['multiple_spouses'] = [];
            $this->merge(['declarant_info' => $declarant]);
        }
    }

    public function rules(): array
    {
        $rules = [
            'type_of_filing' => 'required|in:assumption_of_office,annual_filing,exit',
            'as_of_date' => 'required|date',
            'declarant_info' => 'required|array',
            'declarant_info.family_name' => 'required|string|max:255',
            'declarant_info.first_name' => 'required|string|max:255',
            'declarant_info.middle_initial' => 'nullable|string|max:3',
            'declarant_info.position' => 'required|string|max:255',
            'declarant_info.agency' => 'required|string|max:255',
            'declarant_info.office_address' => 'required|string|max:500',
            'declarant_info.date_accomplished' => 'nullable|date',
            'declarant_info.government_issued_id' => 'nullable|string|max:255',
            'declarant_info.id_no' => 'nullable|string|max:255',
            'declarant_info.date_issued' => 'nullable|string|max:255',
            'declarant_info.person_administering_oath' => 'nullable|string|max:255',
            'declarant_info.multiple_marriages_not_applicable' => 'nullable|boolean',
            'declarant_info.multiple_spouses' => 'nullable|array',
            'declarant_info.multiple_spouses.*' => 'nullable|string|max:255',
            'spouse_info' => 'nullable|array',
            'spouse_info.family_name' => 'nullable|string|max:255',
            'spouse_info.first_name' => 'nullable|string|max:255',
            'spouse_info.middle_initial' => 'nullable|string|max:3',
            'spouse_info.position' => 'nullable|string|max:255',
            'spouse_info.agency' => 'nullable|string|max:255',
            'spouse_info.office_address' => 'nullable|string|max:500',
            'spouse_info.date_accomplished' => 'nullable|date',
            'spouse_info.government_issued_id' => 'nullable|string|max:255',
            'spouse_info.id_no' => 'nullable|string|max:255',
            'spouse_info.date_issued' => 'nullable|string|max:255',
            'filing_status' => 'required|in:joint,separate,not_applicable',
            'children' => 'nullable|array',
            'children.*.name' => 'nullable|string|max:255',
            'children.*.age' => 'nullable|integer|min:0|max:17',
            'children.*.pds_child_id' => 'nullable|integer',
            'children.*.date_of_birth' => 'nullable|date',
            'real_properties' => 'nullable|array',
            'real_properties.*.description' => 'required_with:real_properties.*.kind|nullable|string|max:255',
            'real_properties.*.kind' => 'nullable|string|max:255',
            'real_properties.*.location' => 'nullable|string|max:255',
            'real_properties.*.assessed_value' => 'nullable|numeric|min:0',
            'real_properties.*.fair_market_value' => 'nullable|numeric|min:0',
            'real_properties.*.acquisition_year' => 'nullable|string|max:4',
            'real_properties.*.acquisition_mode' => 'nullable|string|max:255',
            'real_properties.*.acquisition_cost' => 'nullable|numeric|min:0',
            'personal_properties' => 'nullable|array',
            'personal_properties.*.description' => 'required_with:personal_properties.*.acquisition_cost|nullable|string|max:255',
            'personal_properties.*.acquisition_year' => 'nullable|string|max:4',
            'personal_properties.*.acquisition_cost' => 'nullable|numeric|min:0',
            'liabilities' => 'nullable|array',
            'liabilities.*.nature' => 'required_with:liabilities.*.creditor|nullable|string|max:255',
            'liabilities.*.creditor' => 'nullable|string|max:255',
            'liabilities.*.outstanding_balance' => 'nullable|numeric|min:0',
            'has_business_interests' => 'required|boolean',
            'has_relatives_in_gov' => 'required|boolean',
        ];

        if ($this->boolean('has_business_interests')) {
            $rules['business_interests'] = 'required|array|min:1';
            $rules['business_interests.*.name'] = 'required|string|max:255';
            $rules['business_interests.*.address'] = 'required|string|max:255';
            $rules['business_interests.*.nature'] = 'required|string|max:255';
            $rules['business_interests.*.acquisition_date'] = 'required|date';
        }

        if ($this->boolean('has_relatives_in_gov')) {
            $rules['relatives_in_gov'] = 'required|array|min:1';
            $rules['relatives_in_gov.*.name'] = 'required|string|max:255';
            $rules['relatives_in_gov.*.relationship'] = 'required|string|max:255';
            $rules['relatives_in_gov.*.position'] = 'required|string|max:255';
            $rules['relatives_in_gov.*.agency'] = 'required|string|max:255';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'as_of_date.required' => 'Please select a compliance date under Compliance For.',
            'declarant_info.family_name.required' => 'Declarant family name is required.',
            'declarant_info.first_name.required' => 'Declarant first name is required.',
            'business_interests.required' => 'Add at least one business interest or check the "no business interest" box.',
            'relatives_in_gov.required' => 'Add at least one relative or check the "no relatives in government" box.',
        ];
    }

    private function filterRows(?array $rows, array $fields): array
    {
        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_filter($rows, function ($row) use ($fields) {
            if (! is_array($row)) {
                return false;
            }

            foreach ($fields as $field) {
                if (filled($row[$field] ?? null)) {
                    return true;
                }
            }

            return false;
        }));
    }
}
