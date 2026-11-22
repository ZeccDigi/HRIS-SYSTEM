@php
    $canManageSalary = in_array(strtolower(auth()->user()->role), ['admin', 'hrstaff'], true);
    $grades = $optionsByGroup->get('salary_grades', collect())->where('is_active', true);
    $steps = $optionsByGroup->get('step_increments', collect())->where('is_active', true);
@endphp
<section class="space-y-4" x-data="salaryRateFilters">
    <div><h3 class="text-lg font-bold text-slate-900">Salary Rate</h3><p class="mt-1 text-xs text-slate-500">Set the official monthly basic salary for each year, grade, and step. Add grades and steps in their respective Utilities tabs.</p></div>
    @if($errors->any())<div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @if($canManageSalary)
    <form method="POST" action="{{ route('utilities.salary-rates.store') }}" class="grid items-end gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-6">
        @csrf
        <label class="text-xs font-semibold text-slate-600">Salary Year<input name="year" type="number" min="2000" max="{{ now()->year + 10 }}" value="{{ old('year', now()->year) }}" required class="mt-1 block h-10 w-full rounded-lg border-slate-300 text-sm"></label>
        <label class="text-xs font-semibold text-slate-600">Salary Grade<select name="salary_grade" required class="mt-1 block h-10 w-full rounded-lg border-slate-300 text-sm"><option value="">Select grade</option>@foreach($grades as $option)<option value="{{ $option->value }}" @selected(old('salary_grade') === $option->value)>{{ $option->label }}</option>@endforeach</select></label>
        <label class="text-xs font-semibold text-slate-600">Step<select name="salary_step" required class="mt-1 block h-10 w-full rounded-lg border-slate-300 text-sm"><option value="">Select step</option>@foreach($steps as $option)<option value="{{ $option->value }}" @selected(old('salary_step') === $option->value)>{{ $option->label }}</option>@endforeach</select></label>
        <label class="text-xs font-semibold text-slate-600">Monthly Salary (₱)<input name="monthly_salary" type="number" min="0.01" max="99999999.99" step="0.01" value="{{ old('monthly_salary') }}" required class="mt-1 block h-10 w-full rounded-lg border-slate-300 text-sm"></label>
        <label class="text-xs font-semibold text-slate-600">Status<select name="is_active" class="mt-1 block h-10 w-full rounded-lg border-slate-300 text-sm"><option value="1">Active</option><option value="0">Inactive</option></select></label>
        <button class="h-10 rounded-lg bg-blue-900 px-4 text-xs font-bold uppercase text-white">Add Rate</button>
    </form>
    @endif
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <form method="GET" action="{{ route('utilities.index') }}" x-ref="filters" @submit.prevent="load()" @input.debounce.350ms="if ($event.target.type === 'search') load()" @change="if ($event.target.tagName === 'SELECT') load()" class="flex flex-nowrap items-center gap-2 border-b border-slate-200 bg-slate-50/50 p-3">
            <input type="hidden" name="tab" value="salary-schedule">
            <input type="search" name="salary_search" value="{{ $salaryFilters['salary_search'] ?? '' }}" placeholder="Search SG, step or salary…" aria-label="Search salary rates" class="h-9 min-w-36 flex-1 rounded-lg border-slate-300 text-xs">
            <select name="salary_year" aria-label="Filter salary year" class="h-9 min-w-0 flex-1 rounded-lg border-slate-300 py-1 text-xs"><option value="">All Years</option>@foreach($salaryYears as $year)<option value="{{ $year }}" @selected((string) ($salaryFilters['salary_year'] ?? '') === (string) $year)>{{ $year }}</option>@endforeach</select>
            <select name="salary_grade" aria-label="Filter salary grade" class="h-9 min-w-0 flex-1 rounded-lg border-slate-300 py-1 text-xs"><option value="">All Grades</option>@foreach($grades as $grade)<option value="{{ $grade->value }}" @selected(($salaryFilters['salary_grade'] ?? '') === $grade->value)>{{ $grade->label }}</option>@endforeach</select>
            <select name="salary_step" aria-label="Filter salary step" class="h-9 min-w-0 flex-1 rounded-lg border-slate-300 py-1 text-xs"><option value="">All Steps</option>@foreach($steps as $step)<option value="{{ $step->value }}" @selected(($salaryFilters['salary_step'] ?? '') === $step->value)>{{ $step->label }}</option>@endforeach</select>
            <select name="salary_status" aria-label="Filter salary status" class="h-9 min-w-0 flex-1 rounded-lg border-slate-300 py-1 text-xs"><option value="">All Statuses</option><option value="active" @selected(($salaryFilters['salary_status'] ?? '') === 'active')>Active</option><option value="inactive" @selected(($salaryFilters['salary_status'] ?? '') === 'inactive')>Inactive</option></select>
            <input type="hidden" name="salary_sort" value="{{ $salaryFilters['salary_sort'] ?? 'salary_asc' }}">
            <button type="button" @click="sort()" :title="descending ? 'Highest to lowest' : 'Lowest to highest'" :aria-label="descending ? 'Sort: highest to lowest' : 'Sort: lowest to highest'" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-300"><i :class="descending ? 'fa-solid fa-arrow-down-wide-short' : 'fa-solid fa-arrow-up-wide-short'"></i></button>
            <button type="button" @click="reset()" class="h-9 shrink-0 rounded-lg border border-slate-300 px-3 text-xs">Reset</button>
        </form>
        <div x-ref="results" @click="if ($event.target.closest('a[href]')) { $event.preventDefault(); load($event.target.closest('a').href) }" :aria-busy="loading">@include('utilities.salary-rate-results')</div>
        <p x-show="failed" x-cloak role="alert" class="p-3 text-sm text-red-700">Unable to load results. <button type="button" @click="load()" class="underline">Try again</button></p>
    </div>
</section>
