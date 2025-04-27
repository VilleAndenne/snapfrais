<?php

namespace App\Http\Controllers;

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
        if (!auth()->user()->can('viewAny', Department::class)) {
            return redirect()->route('dashboard')->with('error', 'Vous n\'avez pas la permission de faire ceci.');
        }

        $departments = Department::with(['users' => function ($query) {
            $query->withPivot('is_head');
        }])->where('organization_id', auth()->user()->organization_id)->get();

        return Inertia::render('departments/Index', [
            'departments' => $departments,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public
    function create()
    {
        if (!auth()->user()->can('create', Department::class)) {
            return redirect()->route('dashboard')->with('error', 'Vous n\'avez pas la permission de faire ceci.');
        }
        return Inertia::render('departments/Create', [
            'users' => \App\Models\User::all(),
            'departments' => Department::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public
    function store(Request $request)
    {
        if (!auth()->user()->can('create', Department::class)) {
            return redirect()->route('dashboard')->with('error', 'Vous n\'avez pas la permission de faire ceci.');
        }
        $validated = $request->validate([
            'name' => 'required',
            'parent_id' => 'nullable',
            'users' => 'array',
            'users.*.id' => 'integer|exists:users,id',
            'users.*.is_head' => 'boolean',
        ]);

        $dep = Department::create([
            'name' => $validated['name'],
            'parent_id' => $validated['parent_id'],
            'organization_id' => auth()->user()->organization_id,
        ]);

        $syncData = [];
        foreach ($validated['users'] as $user) {
            $syncData[$user['id']] = ['is_head' => $user['is_head']];
        }

        $dep->users()->sync($syncData);

        return redirect()->route('departments.index');
    }

    /**
     * Display the specified resource.
     */
    public
    function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public
    function edit(string $id)
    {
        if (!auth()->user()->can('update', Department::findOrFail($id))) {
            return redirect()->route('dashboard')->with('error', 'Vous n\'avez pas la permission de faire ceci.');
        }
        return Inertia::render('departments/Edit', [
            'department' => Department::with(['users' => function ($query) {
                $query->withPivot('is_head');
            }])->find($id),
            'users' => \App\Models\User::all(),
            'departments' => Department::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public
    function update(Request $request, string $id)
    {
        if (!auth()->user()->can('update', Department::findOrFail($id))) {
            return redirect()->route('dashboard')->with('error', 'Vous n\'avez pas la permission de faire ceci.');
        }
        $validated = $request->validate([
            'name' => 'required',
            'parent_id' => 'nullable',
            'users' => 'array',
            'users.*.id' => 'integer|exists:users,id',
            'users.*.is_head' => 'boolean',
        ]);

        $dep = Department::find($id);

        $dep->update([
            'name' => $validated['name'],
            'parent_id' => $validated['parent_id'],
        ]);

        $syncData = [];
        foreach ($validated['users'] as $user) {
            $syncData[$user['id']] = ['is_head' => $user['is_head']];
        }

        $dep->users()->sync($syncData);

        return redirect()->route('departments.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public
    function destroy(string $id)
    {
        if (!auth()->user()->can('destroy', Department::findOrFail($id))) {
            return redirect()->route('dashboard')->with('error', 'Vous n\'avez pas la permission de faire ceci.');
        }
        Department::destroy($id);

        return redirect()->route('departments.index');
    }
}
