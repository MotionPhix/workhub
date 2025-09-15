<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $departments = Department::select([
            'id',
            'uuid',
            'name',
            'description',
            'working_hours_per_day',
            'daily_tasks_target',
            'quality_target_percentage',
        ])->get();

        return Inertia::render('admin/departments/Index', [
            'departments' => $departments,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('admin/departments/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:departments',
            'description' => 'nullable|string',
            'working_hours_per_day' => 'required|numeric|min:0|max:24',
            'daily_tasks_target' => 'required|integer|min:1|max:50',
            'quality_target_percentage' => 'required|numeric|min:0|max:100',
        ]);

        Department::create($validated);

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Department $department)
    {
        return Inertia::render('admin/departments/Show', [
            'department' => $department,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Department $department)
    {
        return Inertia::render('admin/departments/Edit', [
            'department' => $department,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:departments,name,'.$department->id,
            'description' => 'nullable|string',
            'working_hours_per_day' => 'required|numeric|min:0|max:24',
            'daily_tasks_target' => 'required|integer|min:1|max:50',
            'quality_target_percentage' => 'required|numeric|min:0|max:100',
        ]);

        $department->update($validated);

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department targets updated successfully.');
    }

    /**
     * Update department targets specifically (API endpoint for quick updates)
     */
    public function updateTargets(Request $request, Department $department)
    {
        $validated = $request->validate([
            'working_hours_per_day' => 'required|numeric|min:0|max:24',
            'daily_tasks_target' => 'required|integer|min:1|max:50',
            'quality_target_percentage' => 'required|numeric|min:0|max:100',
        ]);

        $department->update($validated);

        return response()->json([
            'message' => 'Department targets updated successfully.',
            'department' => $department->fresh(),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Department $department)
    {
        // Check if department has users
        if ($department->users()->count() > 0) {
            return redirect()->route('admin.departments.index')
                ->with('error', 'Cannot delete department with existing users.');
        }

        $department->delete();

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department deleted successfully.');
    }
}
