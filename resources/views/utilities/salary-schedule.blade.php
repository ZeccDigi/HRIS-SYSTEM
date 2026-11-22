@php
    $canManageSalary = in_array(strtolower(auth()->user()->role), ['admin', 'hrstaff'], true);
    $grades = $optionsByGroup->get('salary_grades', collect())->where('is_active', true);
    $steps = $optionsByGroup->get('step_increments', collect())->where('is_active', true);
@endphp
<section class="space-y-4" x-data="{ editing: null }">
    <div><h3 class="text-lg font-bold text-slate-900">Salary Schedule</h3><p class="mt-1 text-xs text-slate-500">Set the official monthly basic salary for each year, grade, and step. Add grades and steps in their respective Utilities tabs.</p></div>
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
        <form method="GET" action="{{ route('utilities.index') }}" class="flex flex-wrap items-center gap-2 border-b border-slate-200 bg-slate-50/50 p-3">
            <input type="hidden" name="tab" value="salary-schedule">
            <input type="search" name="salary_search" value="{{ $salaryFilters['salary_search'] ?? '' }}" placeholder="Search SG, step or salary…" aria-label="Search salary rates" class="h-9 min-w-48 flex-1 rounded-lg border-slate-300 text-xs">
            <select name="salary_year" aria-label="Filter salary year" class="h-9 rounded-lg border-slate-300 py-1 text-xs"><option value="">All Years</option>@foreach($salaryYears as $year)<option value="{{ $year }}" @selected((string) ($salaryFilters['salary_year'] ?? '') === (string) $year)>{{ $year }}</option>@endforeach</select>
            <select name="salary_grade" aria-label="Filter salary grade" class="h-9 rounded-lg border-slate-300 py-1 text-xs"><option value="">All Grades</option>@foreach($grades as $grade)<option value="{{ $grade->value }}" @selected(($salaryFilters['salary_grade'] ?? '') === $grade->value)>{{ $grade->label }}</option>@endforeach</select>
            <select name="salary_step" aria-label="Filter salary step" class="h-9 rounded-lg border-slate-300 py-1 text-xs"><option value="">All Steps</option>@foreach($steps as $step)<option value="{{ $step->value }}" @selected(($salaryFilters['salary_step'] ?? '') === $step->value)>{{ $step->label }}</option>@endforeach</select>
            <select name="salary_status" aria-label="Filter salary status" class="h-9 rounded-lg border-slate-300 py-1 text-xs"><option value="">All Statuses</option><option value="active" @selected(($salaryFilters['salary_status'] ?? '') === 'active')>Active</option><option value="inactive" @selected(($salaryFilters['salary_status'] ?? '') === 'inactive')>Inactive</option></select>
            <select name="salary_sort" aria-label="Sort salary amount" class="h-9 rounded-lg border-slate-300 py-1 text-xs"><option value="salary_asc" @selected(($salaryFilters['salary_sort'] ?? 'salary_asc') === 'salary_asc')>Lowest to Highest</option><option value="salary_desc" @selected(($salaryFilters['salary_sort'] ?? '') === 'salary_desc')>Highest to Lowest</option></select>
            <button class="h-9 rounded-lg bg-blue-900 px-3 text-xs font-semibold text-white">Apply</button>
            <a href="{{ route('utilities.index', ['tab' => 'salary-schedule']) }}" class="inline-flex h-9 items-center rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700">Reset</a>
        </form>
        <table class="w-full text-left text-xs"><thead class="bg-slate-50 text-[10px] uppercase text-slate-500"><tr><th class="px-4 py-3">Salary Year</th><th class="px-4 py-3">Salary Grade</th><th class="px-4 py-3">Step</th><th class="px-4 py-3">Monthly Salary</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Actions</th></tr></thead>
        <tbody class="divide-y divide-slate-100">@forelse($salaryRates as $rate)
            <tr><td class="px-4 py-3">{{ $rate->year }}</td><td class="px-4 py-3">{{ $rate->salary_grade }}</td><td class="px-4 py-3">{{ $rate->salary_step }}</td><td class="px-4 py-3 font-semibold">₱{{ number_format((float) $rate->monthly_salary, 2) }}</td><td class="px-4 py-3">{{ $rate->is_active ? 'Active' : 'Inactive' }}</td><td class="px-4 py-3">@if($canManageSalary)<button type="button" @click="editing = editing === {{ $rate->id }} ? null : {{ $rate->id }}" aria-label="Edit salary rate" class="text-blue-900"><i class="fa-solid fa-pen"></i></button>@else -- @endif</td></tr>
            @if($canManageSalary)<tr x-show="editing === {{ $rate->id }}" x-cloak><td colspan="6" class="p-3"><form method="POST" action="{{ route('utilities.salary-rates.update', $rate) }}" class="flex flex-wrap items-end gap-3">@csrf @method('PUT')
                <input type="hidden" name="year" value="{{ $rate->year }}"><input type="hidden" name="salary_grade" value="{{ $rate->salary_grade }}"><input type="hidden" name="salary_step" value="{{ $rate->salary_step }}">
                <label class="text-xs text-slate-600">Monthly Salary (₱)<input name="monthly_salary" value="{{ $rate->monthly_salary }}" type="number" min="0.01" max="99999999.99" step="0.01" required class="mt-1 block h-9 rounded-lg border-slate-300 text-xs"></label>
                <label class="text-xs text-slate-600">Status<select name="is_active" class="mt-1 block h-9 rounded-lg border-slate-300 text-xs"><option value="1" @selected($rate->is_active)>Active</option><option value="0" @selected(!$rate->is_active)>Inactive</option></select></label>
                <button class="h-9 rounded-lg bg-blue-900 px-4 text-xs font-bold text-white">Save</button><button type="button" @click="editing = null" class="h-9 rounded-lg border border-slate-300 px-4 text-xs">Cancel</button>
            </form></td></tr>@endif
        @empty<tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No salary rates found. Adjust the filters or add a salary rate.</td></tr>@endforelse</tbody></table>
        <div class="border-t border-slate-100 p-3">{{ $salaryRates->links() }}</div>
    </div>
</section>
