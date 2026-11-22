<?php

namespace App\Http\Controllers;

use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('utilities.index', ['tab' => 'leave-types']);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('leaves.types.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:leave_types,name',
            'description' => 'nullable|string',
            'legal_basis' => 'nullable|string|max:255',
            'days_per_year' => 'nullable|integer|min:0',
        ]);

        LeaveType::create($validated);

        return redirect()->route('utilities.index', ['tab' => 'leave-types'])->with('success', 'Leave type created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(LeaveType $leaveType)
    {
        return redirect()->back();
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LeaveType $leaveType): View
    {
        return view('leaves.types.edit', compact('leaveType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LeaveType $leaveType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:leave_types,name,'.$leaveType->id,
            'description' => 'nullable|string',
            'legal_basis' => 'nullable|string|max:255',
            'days_per_year' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $leaveType->update($validated);

        return redirect()->route('utilities.index', ['tab' => 'leave-types'])->with('success', 'Leave type updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LeaveType $leaveType): RedirectResponse
    {
        $leaveType->delete();

        return redirect()->route('utilities.index', ['tab' => 'leave-types'])->with('success', 'Leave type deleted successfully.');
    }
}
