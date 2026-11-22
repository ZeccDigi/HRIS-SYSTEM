@php
    \App\Support\UtilityOptionRegistry::ensureDefaults();
    $salaryEmployee = $employee ?? null;
    $salaryRates = \App\Models\SalaryRate::where('is_active', true)->where('year', '<=', now()->year)->get();
    $salaryGrades = \App\Models\UtilityOption::listFor('salary_grades');
    $salarySteps = \App\Models\UtilityOption::listFor('step_increments');
    $salaryYears = collect(range((int) now()->year, 2000))->merge($salaryRates->pluck('year'))->push($salaryEmployee?->salary_schedule_year)->push(old('salary_schedule_year'))->filter()->unique()->sortDesc();
    $canEditSalary = in_array(strtolower(auth()->user()->role), ['admin', 'hrstaff'], true);
@endphp
<fieldset @disabled(!$canEditSalary) class="mt-4 grid grid-cols-1 gap-4 border-t border-slate-100 pt-4 sm:grid-cols-2 lg:grid-cols-4" x-data="{
    grade: @js(old('salary_grade', $salaryEmployee?->salary_grade ?? '')),
    step: @js(old('salary_step', $salaryEmployee?->salary_step ?? '')),
    salaryYear: @js(old('salary_schedule_year', $salaryEmployee?->salary_schedule_year ?? (int) now()->year)),
    currentSalary: @js(old('monthly_salary', $salaryEmployee?->monthly_salary ?? '')),
    rates: @js($salaryRates),
    matchingRate() { return this.rates.find(r => r.salary_grade === this.grade && r.salary_step === this.step && String(r.year) === String(this.salaryYear)); },
    updateSalary() { const rate = this.matchingRate(); if (rate) this.currentSalary = rate.monthly_salary; }
}">
    <label class="block text-sm font-medium text-gray-700">Salary Grade<select name="salary_grade" x-model="grade" @change="updateSalary()" class="mt-1 block w-full rounded-md border-gray-300 text-sm"><option value="">No salary grade</option>@foreach($salaryGrades as $option)<option value="{{ $option->value }}">{{ $option->label }}</option>@endforeach</select><x-input-error :messages="$errors->get('salary_grade')" /></label>
    <label class="block text-sm font-medium text-gray-700">Step<select name="salary_step" x-model="step" @change="updateSalary()" :required="!!grade" class="mt-1 block w-full rounded-md border-gray-300 text-sm"><option value="">Select step</option>@foreach($salarySteps as $option)<option value="{{ $option->value }}">{{ $option->label }}</option>@endforeach</select><x-input-error :messages="$errors->get('salary_step')" /></label>
    <label class="block text-sm font-medium text-gray-700">Salary Year<select name="salary_schedule_year" x-model="salaryYear" @change="updateSalary()" :required="!!grade" class="mt-1 block w-full rounded-md border-gray-300 text-sm">@foreach($salaryYears as $optionYear)<option value="{{ $optionYear }}">{{ $optionYear }}</option>@endforeach</select><x-input-error :messages="$errors->get('salary_schedule_year')" /></label>
    <label class="block text-sm font-medium text-gray-700">Salary (₱)<input name="monthly_salary" type="text" inputmode="decimal" x-money="currentSalary" :readonly="!!matchingRate()" class="mt-1 block w-full rounded-md border-gray-300 text-sm" :class="matchingRate() ? 'bg-slate-50' : 'bg-white'"><x-input-error :messages="$errors->get('monthly_salary')" /></label>

</fieldset>
