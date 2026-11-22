<?php

namespace App\Http\Controllers;

use App\Models\LeaveType;
use App\Models\UtilityOption;
use App\Models\SalaryRate;
use Illuminate\Validation\Rule;
use App\Support\UtilityOptionRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UtilityController extends Controller
{
    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        if (! $request->expectsJson()) UtilityOptionRegistry::ensureDefaults();

        $tabs = UtilityOptionRegistry::tabs();
        $tab = $request->query('tab', $request->session()->get('utilities.tab', 'leave-types'));
        if (! is_string($tab)) $tab = 'leave-types';
        if (! array_key_exists($tab, $tabs)) {
            $tab = 'leave-types';
        }

        $groups = UtilityOptionRegistry::groups();
        $request->session()->put('utilities.tab', $tab);
        $leaveTypes = $request->expectsJson() ? collect() : LeaveType::query()->latest()->get();
        $optionsByGroup = $request->expectsJson() ? collect() : UtilityOption::query()
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->groupBy('group_key');

        $salaryFilters = $request->validate([
            'salary_search' => 'nullable|string|max:100', 'salary_year' => 'nullable|integer|min:2000|max:2100',
            'salary_grade' => 'nullable|string|max:50', 'salary_step' => 'nullable|string|max:50',
            'salary_status' => 'nullable|in:active,inactive', 'salary_sort' => 'nullable|in:salary_asc,salary_desc',
        ]);
        $salaryYears = SalaryRate::distinct()->orderByDesc('year')->pluck('year');
        $query = SalaryRate::query();
        $search = trim($salaryFilters['salary_search'] ?? '');
        if ($search !== '') $query->where(function ($query) use ($search) {
            $query->where('salary_grade', 'like', '%'.$search.'%')->orWhere('salary_step', 'like', '%'.$search.'%')
                ->orWhereRaw('CAST(monthly_salary AS '.(in_array(\Illuminate\Support\Facades\DB::getDriverName(), ['mysql', 'mariadb'], true) ? 'CHAR' : 'TEXT').') LIKE ?', ['%'.str_replace(',', '', $search).'%']);
        });
        foreach (['salary_year' => 'year', 'salary_grade' => 'salary_grade', 'salary_step' => 'salary_step'] as $filter => $column) {
            if (filled($salaryFilters[$filter] ?? null)) $query->where($column, $salaryFilters[$filter]);
        }
        if (filled($salaryFilters['salary_status'] ?? null)) $query->where('is_active', $salaryFilters['salary_status'] === 'active');
        $salaryRates = $query->orderBy('monthly_salary', ($salaryFilters['salary_sort'] ?? 'salary_asc') === 'salary_desc' ? 'desc' : 'asc')
            ->orderByDesc('year')->orderBy('id')->paginate(20, ['*'], 'salary_page')->withQueryString();
        if ($request->expectsJson() && $tab === 'salary-schedule') {
            $canManageSalary = in_array(strtolower($request->user()->role), ['admin', 'hrstaff'], true);
            return response()->json(['html' => view('utilities.salary-rate-results', compact('salaryRates', 'canManageSalary'))->render()]);
        }
        return view('utilities.index', compact('tab', 'tabs', 'groups', 'leaveTypes', 'optionsByGroup', 'salaryRates', 'salaryYears', 'salaryFilters'));
    }

    public function saveSalaryRate(Request $request, ?SalaryRate $salaryRate = null): RedirectResponse
    {
        abort_unless(in_array(strtolower($request->user()->role), ['admin', 'hrstaff'], true), 403);
        UtilityOptionRegistry::ensureDefaults();
        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:'.(now()->year + 10)],
            'salary_grade' => ['required', Rule::in(UtilityOption::listFor('salary_grades')->pluck('value')->all()),
                Rule::unique('salary_rates')->where(fn ($q) => $q->where('year', $request->input('year'))->where('salary_step', $request->input('salary_step')))->ignore($salaryRate?->id)],
            'salary_step' => ['required', Rule::in(UtilityOption::listFor('step_increments')->pluck('value')->all())],
            'monthly_salary' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999.99'],
            'is_active' => ['required', 'boolean'],
        ], ['salary_grade.unique' => 'This grade and step already have a salary for the selected year. Edit that rate instead.']);
        if ($salaryRate?->exists) $salaryRate->update($data);
        else SalaryRate::create($data);
        \App\Listeners\AuditLogListener::logActivity($salaryRate?->exists ? 'Update Salary Rate' : 'Add Salary Rate', 'Utilities', $data['year'].' '.$data['salary_grade'].' '.$data['salary_step'].' monthly salary '.$data['monthly_salary'], $request->user());
        return redirect()->route('utilities.index', ['tab' => 'salary-schedule'])->with('success', 'Salary rate saved. Existing approved PDS and filed requests retain their saved salary.');
    }

    public function storeOption(Request $request): RedirectResponse
    {
        $groups = UtilityOptionRegistry::groups();

        $validated = $request->validate([
            'group_key' => ['required', 'string'],
            'label' => ['required', 'string', 'max:255'],
            'value' => ['nullable', 'string', 'max:255'],
            'parent_value' => ['nullable', 'string', 'max:255'],
        ]);

        abort_unless(array_key_exists($validated['group_key'], $groups), 404);

        $nextSort = ((int) UtilityOption::query()->where('group_key', $validated['group_key'])->max('sort_order')) + 1;

        UtilityOption::create([
            'group_key' => $validated['group_key'],
            'label' => $validated['label'],
            'value' => ($validated['value'] ?? null) ?: $validated['label'],
            'parent_group' => $groups[$validated['group_key']]['parent_group'] ?? null,
            'parent_value' => $validated['parent_value'] ?? null,
            'sort_order' => $nextSort,
            'is_active' => true,
        ]);

        return redirect()
            ->route('utilities.index', ['tab' => $validated['group_key']])
            ->with('success', 'Utility option added.');
    }

    public function updateOption(Request $request, UtilityOption $utilityOption): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'value' => ['nullable', 'string', 'max:255'],
            'parent_value' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $utilityOption->update([
            'label' => $validated['label'],
            'value' => ($validated['value'] ?? null) ?: $validated['label'],
            'parent_value' => $validated['parent_value'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('utilities.index', ['tab' => $utilityOption->group_key])
            ->with('success', 'Utility option updated.');
    }

    public function destroySalaryRate(SalaryRate $salaryRate): RedirectResponse
    {
        $salaryRate->delete();
        return redirect()->route('utilities.index', ['tab' => 'salary-schedule'])->with('success', 'Salary rate archived.');
    }

    public function destroyOption(UtilityOption $utilityOption): RedirectResponse
    {
        $tab = $utilityOption->group_key;
        $utilityOption->delete();

        return redirect()
            ->route('utilities.index', ['tab' => $tab])
            ->with('success', 'Utility option archived.');
    }
}
