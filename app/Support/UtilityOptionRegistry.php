<?php

namespace App\Support;

use App\Models\PdsPersonalInformation;
use App\Models\UtilityOption;

class UtilityOptionRegistry
{
    public static function tabs(): array
    {
        return [
            'leave-types' => 'Leave Types',
            'divisions' => 'Divisions',
            'name_extensions' => 'Name Extension',
            'civil_statuses' => 'Civil Status',
            'blood_types' => 'Blood Type',
            'citizenship_types' => 'Citizenship Type',
            'education_levels' => 'Educational Level',
            'highest_levels' => 'Educational Attainment',
            'eligibility_titles' => 'Eligibility Title',
            'salary_grades' => 'Salary Grade',
            'salary-schedule' => 'Salary Schedule',
            'step_increments' => 'Step Increment',
            'appointment_statuses' => 'Appointment Status',
            'government_service_options' => 'Government Service',
            'government_id_types' => 'Government ID Type',
            'property_kinds' => 'Property Kind',
            'acquisition_modes' => 'Mode of Acquisition',
            'relationships' => 'Relationship',
            'countries' => 'Country',
            'ph_regions' => 'Region',
            'ph_provinces' => 'Province',
            'ph_cities' => 'City / Municipality',
            'ph_barangays' => 'Barangay',
        ];
    }

    public static function groups(): array
    {
        return [
            'divisions' => ['label' => 'Divisions'],
            'name_extensions' => ['label' => 'Name Extension'],
            'civil_statuses' => ['label' => 'Civil Status'],
            'blood_types' => ['label' => 'Blood Type'],
            'citizenship_types' => ['label' => 'Citizenship Type'],
            'education_levels' => ['label' => 'Educational Level'],
            'highest_levels' => ['label' => 'Highest Level / Units Earned'],
            'eligibility_titles' => ['label' => 'Eligibility Title'],
            'salary_grades' => ['label' => 'Salary Grade'],
            'step_increments' => ['label' => 'Step Increment'],
            'appointment_statuses' => ['label' => 'Appointment Status'],
            'government_service_options' => ['label' => 'Government Service'],
            'government_id_types' => ['label' => 'Government-Issued ID Type'],
            'property_kinds' => ['label' => 'Property Kind'],
            'acquisition_modes' => ['label' => 'Mode of Acquisition'],
            'relationships' => ['label' => 'Relationship'],
            'countries' => ['label' => 'Country'],
            'ph_regions' => ['label' => 'Region'],
            'ph_provinces' => ['label' => 'Province', 'parent_group' => 'ph_regions'],
            'ph_cities' => ['label' => 'City / Municipality', 'parent_group' => 'ph_provinces'],
            'ph_barangays' => ['label' => 'Barangay', 'parent_group' => 'ph_cities'],
        ];
    }

    public static function ensureDefaults(): void
    {
        foreach (self::defaultOptions() as $groupKey => $options) {
            foreach ($options as $index => $option) {
                UtilityOption::firstOrCreate(
                    [
                        'group_key' => $groupKey,
                        'value' => $option['value'],
                        'parent_value' => $option['parent_value'] ?? null,
                    ],
                    [
                        'label' => $option['label'],
                        'parent_group' => $option['parent_group'] ?? null,
                        'sort_order' => $option['sort_order'] ?? ($index + 1),
                        'is_active' => true,
                    ]
                );
            }
        }

        self::removeDeprecatedOptions();
        self::syncExistingLocations();
    }

    public static function defaultOptions(): array
    {
        $build = fn (array $values) => array_map(fn ($value) => ['label' => $value, 'value' => $value], $values);

        return [
            'divisions' => $build([
                'Welfare and Reintegration Services Division',
                'Migrant Workers Protection Division',
                'Migrant Workers Processing Division',
                'Finance and Administrative Division',
            ]),
            'name_extensions' => $build(['None', 'Jr.', 'Sr.', 'II', 'III', 'IV', 'V']),
            'civil_statuses' => $build(['Single', 'Married', 'Widowed', 'Separated']),
            'blood_types' => $build(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Unknown']),
            'citizenship_types' => $build(['By Birth', 'By Naturalization']),
            'education_levels' => $build(['Elementary', 'Secondary', 'Vocational/Trade Course', 'College', 'Graduate Studies']),
            'highest_levels' => $build(['Elementary', 'Secondary', 'Vocational/Trade Course', 'Graduate Studies', 'Undergraduate', 'Units Earned']),
            'eligibility_titles' => $build(['Career Service Professional', 'Career Service Subprofessional', 'RA 1080', 'PD 907', 'Barangay Eligibility', 'Honor Graduate Eligibility']),
            'salary_grades' => $build(array_map(fn ($n) => 'SG '.$n, range(1, 33))),
            'step_increments' => $build(array_map(fn ($n) => 'Step '.$n, range(1, 8))),
            'appointment_statuses' => $build(['Permanent', 'Temporary', 'Coterminous', 'Casual', 'Contractual', 'Job Order', 'Contract of Service', 'Substitute', 'Provisional']),
            'government_service_options' => $build(['Yes', 'No']),
            'government_id_types' => $build(['Philippine Passport', "Driver's License", 'UMID', 'PhilSys ID', 'PRC ID', 'GSIS ID', 'SSS ID', 'Postal ID', "Voter's ID"]),
            'property_kinds' => $build(['Residential', 'Commercial', 'Agricultural', 'Industrial', 'Mixed Use']),
            'acquisition_modes' => $build(['Purchase', 'Inheritance', 'Donation', 'Exchange', 'Construction']),
            'relationships' => $build(['Spouse', 'Parent', 'Child', 'Sibling', 'Grandparent', 'Grandchild', 'Uncle/Aunt', 'Nephew/Niece', 'Cousin', 'In-Law']),
            'countries' => $build(['Philippines', 'Brunei', 'Cambodia', 'Indonesia', 'Laos', 'Malaysia', 'Myanmar', 'Singapore', 'Thailand', 'Vietnam', 'Japan', 'South Korea', 'China', 'India', 'Australia', 'New Zealand', 'United States', 'Canada', 'United Kingdom']),
            'ph_regions' => $build(['National Capital Region (NCR)', 'Cordillera Administrative Region (CAR)', 'Region I (Ilocos Region)', 'Region II (Cagayan Valley)', 'Region III (Central Luzon)', 'Region IV-A (CALABARZON)', 'MIMAROPA Region', 'Region V (Bicol Region)', 'Region VI (Western Visayas)', 'Negros Island Region (NIR)', 'Region VII (Central Visayas)', 'Region VIII (Eastern Visayas)', 'Region IX (Zamboanga Peninsula)', 'Region X (Northern Mindanao)', 'Region XI (Davao Region)', 'Region XII (SOCCSKSARGEN)', 'Region XIII (Caraga)', 'Bangsamoro Autonomous Region In Muslim Mindanao (BARMM)']),
        ];
    }

    protected static function removeDeprecatedOptions(): void
    {
        UtilityOption::query()
            ->whereIn('group_key', ['name_extensions', 'civil_statuses', 'appointment_statuses'])
            ->where('value', 'Other')
            ->delete();
    }

    protected static function syncExistingLocations(): void
    {
        $locationColumns = [
            'ph_provinces' => ['res_province', 'perm_province'],
            'ph_cities' => ['res_city', 'perm_city'],
            'ph_barangays' => ['res_barangay', 'perm_barangay'],
        ];

        foreach ($locationColumns as $groupKey => $columns) {
            foreach ($columns as $column) {
                PdsPersonalInformation::query()
                    ->whereNotNull($column)
                    ->where($column, '!=', '')
                    ->distinct()
                    ->pluck($column)
                    ->filter()
                    ->each(function ($value) use ($groupKey) {
                        UtilityOption::firstOrCreate(
                            [
                                'group_key' => $groupKey,
                                'value' => $value,
                                'parent_value' => null,
                            ],
                            [
                                'label' => $value,
                                'sort_order' => 9999,
                                'is_active' => true,
                            ]
                        );
                    });
            }
        }
    }
}
