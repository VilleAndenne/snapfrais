<?php

namespace App\Http\Controllers;

use App\Imports\UsersImport;
use App\Models\Department;
use App\Models\User;
use App\Notifications\AdminInitiatedPasswordReset;
use App\Notifications\UserCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (! auth()->user()->can('viewAny', User::class)) {
            return redirect()->route('dashboard')->with('error', 'Vous n\'avez pas la permission de faire ceci.');
        }
        $users = User::query()
            ->when(request('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('users/Index', [
            'users' => $users,
            'filters' => request()->only(['search']),
            'canImpersonate' => auth()->user()->canImpersonate(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (! auth()->user()->can('create', User::class)) {
            return redirect()->route('dashboard')->with('error', 'Vous n\'avez pas la permission de faire ceci.');
        }

        return Inertia::render('users/Create', [
            'departments' => Department::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (! auth()->user()->can('create', User::class)) {
            return redirect()->route('dashboard')->with('error', 'Vous n\'avez pas la permission de faire ceci.');
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'is_admin' => 'boolean',
            'departments' => 'array',
            'departments.*.id' => 'required|integer|exists:departments,id',
            'departments.*.is_head' => 'boolean',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Str::random(40),
            'is_admin' => $validated['is_admin'] ?? false,
        ]);

        $attach = collect($validated['departments'] ?? [])
            ->mapWithKeys(fn (array $dept): array => [$dept['id'] => ['is_head' => $dept['is_head'] ?? false]]);

        if ($attach->isNotEmpty()) {
            $user->departments()->attach($attach);
        }

        $token = Password::broker()->createToken($user);

        $user->notify(new UserCreated($token, $validated['email']));

        return redirect()->route('users.index')->with('success', 'Utilisateur créé. Un lien de définition du mot de passe lui a été envoyé.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::findOrFail($id);

        if (! auth()->user()->can('view', $user)) {
            return redirect()->route('dashboard')->with('error', 'Vous n\'avez pas la permission de faire ceci.');
        }

        $user->load([
            'departments:id,name',
        ]);

        $expenseSheets = $user->expenseSheets()
            ->with(['department:id,name', 'form:id,name'])
            ->latest()
            ->limit(10)
            ->get();

        return Inertia::render('users/Show', [
            'user' => $user,
            'expenseSheets' => $expenseSheets,
            'canImpersonate' => (bool) auth()->user()->super_admin && ! $user->super_admin && auth()->id() !== $user->id,
            'canUpdate' => auth()->user()->can('update', $user),
            'canDelete' => auth()->user()->can('delete', $user),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        if (! auth()->user()->can('update', User::findOrFail($id))) {
            return redirect()->route('dashboard')->with('error', 'Vous n\'avez pas la permission de faire ceci.');
        }
        $user = User::findOrFail($id);
        $user->load('departments:id,name');

        return Inertia::render('users/Edit', [
            'user' => $user,
            'departments' => Department::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if (! auth()->user()->can('update', User::findOrFail($id))) {
            return redirect()->route('dashboard')->with('error', 'Vous n\'avez pas la permission de faire ceci.');
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$id,
            'password' => 'nullable|string|min:8|confirmed',
            'is_admin' => 'boolean',
            'departments' => 'array',
            'departments.*.id' => 'required|integer|exists:departments,id',
            'departments.*.is_head' => 'boolean',
        ]);

        $user = User::findOrFail($id);
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->is_admin = $validated['is_admin'] ?? false;
        if (! empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }
        $user->save();

        $sync = collect($validated['departments'] ?? [])
            ->mapWithKeys(fn (array $dept): array => [$dept['id'] => ['is_head' => $dept['is_head'] ?? false]]);

        $user->departments()->sync($sync);

        return redirect()->route('users.index')->with('success', 'Utilisateur mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        if (! auth()->user()->can('delete', User::findOrFail($id))) {
            return redirect()->route('dashboard')->with('error', 'Vous n\'avez pas la permission de faire ceci.');
        }
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'Utilisateur supprimé avec succès.');
    }

    /**
     * Send a password reset link to the user, initiated by an admin.
     */
    public function sendPasswordReset(User $user)
    {
        if (! auth()->user()->can('update', $user)) {
            return redirect()->route('dashboard')->with('error', 'Vous n\'avez pas la permission de faire ceci.');
        }

        $token = Password::broker()->createToken($user);

        $user->notify(new AdminInitiatedPasswordReset($token, auth()->user()->name));

        return redirect()->back()->with('success', 'Un lien de réinitialisation a été envoyé à '.$user->email.'.');
    }

    public function import()
    {
        if (! auth()->user()->can('create', User::class)) {
            return redirect()->route('dashboard')->with('error', 'Vous n\'avez pas la permission de faire ceci.');
        }

        return Inertia::render('users/Import');
    }

    public function doImport(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        // Envoie le fichier sur le disque partagé "laravel_cloud"
        $path = $request->file('file')->store('imports', 'laravel_cloud');

        // (Optionnel mais utile) Forcer la config runtime au cas où le worker n'a pas la même env
        config([
            'excel.temporary_files.remote_disk' => 'laravel_cloud',
            'excel.temporary_files.remote_prefix' => 'temp/excel',
            'excel.temporary_files.force_resync_remote' => true,
        ]);

        // Très important : préciser le DISQUE où se trouve le fichier
        Excel::queueImport(new UsersImport, $path, 'laravel_cloud');

        return redirect()
            ->route('users.index')
            ->with('success', 'Import démarré. Vous recevrez une notification à la fin.');
    }
}
