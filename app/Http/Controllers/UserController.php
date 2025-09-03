<?php

namespace App\Http\Controllers;

use App\Imports\UsersImport;
use App\Models\Department;
use App\Models\User;
use App\Notifications\UserCreated;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!auth()->user()->can('viewAny', User::class)) {
            return redirect()->route('dashboard')->with('error', 'Vous n\'avez pas la permission de faire ceci.');
        }
        $users = User::query()
            ->when(request('search'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('users/Index', [
            'users' => $users,
            'filters' => request()->only(['search']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!auth()->user()->can('create', User::class)) {
            return redirect()->route('dashboard')->with('error', 'Vous n\'avez pas la permission de faire ceci.');
        }
        return Inertia::render('users/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('create', User::class)) {
            return redirect()->route('dashboard')->with('error', 'Vous n\'avez pas la permission de faire ceci.');
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'is_admin' => 'boolean',
        ]);

        if ($request->has('password')) {
            $validated['password'] = $request->validate([
                'password' => 'required|string|min:8|confirmed',
            ])['password'];
        } else {
            $validated['password'] = bcrypt(Str::random(10)); // Default password
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'is_admin' => $validated['is_admin'] ?? false,
        ]);

        if (!$request->has('password')) {
            // generate a reset password token
            $token = Password::broker()->createToken($user);
        }

        // Send email to user to set their password
        $user->notify(new UserCreated($token, $validated['email']));

        return redirect()->route('users.index')->with('success', 'Utilisatuer créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        if (!auth()->user()->can('update', User::findOrFail($id))) {
            return redirect()->route('dashboard')->with('error', 'Vous n\'avez pas la permission de faire ceci.');
        }
        $user = User::findOrFail($id);

        return Inertia::render('users/Edit', [
            'user' => $user,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if (!auth()->user()->can('update', User::findOrFail($id))) {
            return redirect()->route('dashboard')->with('error', 'Vous n\'avez pas la permission de faire ceci.');
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8|confirmed',
            'is_admin' => 'boolean',
        ]);

        $user = User::findOrFail($id);
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->is_admin = $validated['is_admin'] ?? false;
        if ($validated['password']) {
            $user->password = bcrypt($validated['password']);
        }
        $user->save();

        return redirect()->route('users.index')->with('success', 'Utilisateur mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        if (!auth()->user()->can('delete', User::findOrFail($id))) {
            return redirect()->route('dashboard')->with('error', 'Vous n\'avez pas la permission de faire ceci.');
        }
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'Utilisateur supprimé avec succès.');
    }

    public function import()
    {
        if (!auth()->user()->can('create', User::class)) {
            return redirect()->route('dashboard')->with('error', 'Vous n\'avez pas la permission de faire ceci.');
        }
        return Inertia::render('users/Import');
    }

    public function doImport(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        // Lancer l'import en queue en précisant bien le disque
        Excel::queueImport(new UsersImport, Storage::putFile($request->file('file')));

        return redirect()
            ->route('users.index')
            ->with('success', 'Import démarré. Vous recevrez une notification à la fin.');
    }
}
