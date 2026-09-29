<?php

namespace App\Http\Controllers;

use App\Listeners\AuditLogListener;
use App\Http\Requests\StoreSalnRequest;
use App\Models\Saln;
use App\Models\UtilityOption;
use App\Services\SalnPdfExporter;
use App\Support\UtilityOptionRegistry;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class SalnController extends Controller
{
    public function index()
    {
        $employee = Auth::user()->employee;

        if (! $employee) {
            abort(403, 'User is not linked to an employee record.');
        }

        UtilityOptionRegistry::ensureDefaults();

        $salns = $employee->salns()->orderBy('as_of_date', 'desc')->get();
        $selectedYear = (int) request('year', now()->year);
        $selectedSaln = $salns->first(fn (Saln $saln) => (int) $saln->as_of_date->format('Y') === $selectedYear);
        $isSalnIndex = true;
        $salnOptionSets = $this->optionSets($selectedSaln);

        return view('saln.create', compact('employee', 'salns', 'selectedYear', 'selectedSaln', 'isSalnIndex', 'salnOptionSets'));
    }

    public function create()
    {
        $employee = Auth::user()->employee;

        if (! $employee) {
            abort(403, 'User is not linked to an employee record.');
        }

        UtilityOptionRegistry::ensureDefaults();

        $salns = $employee->salns()->orderBy('as_of_date', 'desc')->get();
        $selectedYear = (int) now()->year;
        $selectedSaln = null;
        $isSalnIndex = false;
        $salnOptionSets = $this->optionSets(null);

        return view('saln.create', compact('employee', 'salns', 'selectedYear', 'selectedSaln', 'isSalnIndex', 'salnOptionSets'));
    }

    public function store(StoreSalnRequest $request)
    {
        $validated = $request->validated();
        $validated['has_business_interests'] = $request->boolean('has_business_interests');
        $validated['has_relatives_in_gov'] = $request->boolean('has_relatives_in_gov');

        $total_assets = 0;
        foreach ($validated['real_properties'] ?? [] as $prop) {
            $total_assets += (float) ($prop['acquisition_cost'] ?? 0);
        }
        foreach ($validated['personal_properties'] ?? [] as $prop) {
            $total_assets += (float) ($prop['acquisition_cost'] ?? 0);
        }

        $total_liabilities = 0;
        foreach ($validated['liabilities'] ?? [] as $liab) {
            $total_liabilities += (float) ($liab['outstanding_balance'] ?? 0);
        }

        $net_worth = $total_assets - $total_liabilities;

        $employee = Auth::user()->employee;
        $payload = array_merge($validated, [
            'employee_id' => $employee->id,
            'total_assets' => $total_assets,
            'total_liabilities' => $total_liabilities,
            'net_worth' => $net_worth,
        ]);

        if ($request->filled('saln_id')) {
            $saln = Saln::where('employee_id', $employee->id)
                ->findOrFail($request->integer('saln_id'));
            $saln->update($payload);
        } else {
            $saln = Saln::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'as_of_date' => $validated['as_of_date'],
                ],
                $payload
            );
        }

        AuditLogListener::logActivity(
            'Save SALN',
            'Employees',
            'Saved Statement of Assets, Liabilities, and Net Worth (SALN) as of ' . $saln->as_of_date->format('F d, Y') . ' for ' . trim(($employee->firstname ?? '') . ' ' . ($employee->lastname ?? '')),
            Auth::user()
        );

        return redirect()
            ->route('salns.index', ['year' => $saln->as_of_date->format('Y')])
            ->with('success', 'SALN saved successfully.');
    }

    public function show(Saln $saln)
    {
        if ($saln->employee_id !== Auth::user()->employee->id) {
            abort(403);
        }
        return view('saln.show', compact('saln'));
    }

    public function download(Saln $saln, SalnPdfExporter $exporter): Response|RedirectResponse
    {
        $employee = Auth::user()->employee;

        if (! $employee || $saln->employee_id !== $employee->id) {
            abort(403);
        }

        try {
            return $exporter->download($saln, request()->boolean('inline'));
        } catch (\Throwable $e) {
            return redirect()
                ->route('salns.show', $saln)
                ->with('error', 'Could not generate PDF. '.$e->getMessage());
        }
    }

    protected function optionSets(?Saln $saln): array
    {
        return [
            'property_kinds' => UtilityOption::listFor('property_kinds')->values(),
            'acquisition_modes' => UtilityOption::listFor('acquisition_modes')->values(),
            'relationships' => UtilityOption::listFor('relationships')->values(),
            'government_id_types' => UtilityOption::listFor('government_id_types')->values(),
        ];
    }
}
