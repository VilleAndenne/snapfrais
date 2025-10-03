<?php

namespace App\Http\Controllers;

use App\Models\PatchNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PatchNoteController extends Controller
{
    /**
     * Afficher la liste des patch notes (admin uniquement)
     */
    public function index()
    {
        if (!auth()->user()->is_admin) {
            abort(403);
        }

        $patchNotes = PatchNote::with('creator')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('admin/PatchNotes/Index', [
            'patchNotes' => $patchNotes,
        ]);
    }

    /**
     * Afficher le formulaire de création (admin uniquement)
     */
    public function create()
    {
        if (!auth()->user()->is_admin) {
            abort(403);
        }

        return Inertia::render('admin/PatchNotes/Create');
    }

    /**
     * Enregistrer une nouvelle patch note (admin uniquement)
     */
    public function store(Request $request)
    {
        if (!auth()->user()->is_admin) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        PatchNote::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('patch-notes.index')->with('success', 'Patch note créée avec succès.');
    }

    /**
     * Récupérer les patch notes non lues pour l'utilisateur connecté
     */
    public function getUnread()
    {
        $unreadPatchNotes = auth()->user()->unreadPatchNotes()->get();

        return response()->json($unreadPatchNotes);
    }

    /**
     * Marquer une patch note comme lue pour l'utilisateur connecté
     */
    public function markAsRead($id)
    {
        $patchNote = PatchNote::findOrFail($id);

        // Vérifier si déjà lue
        if (!$patchNote->isReadBy(auth()->user())) {
            auth()->user()->readPatchNotes()->attach($patchNote->id, [
                'read_at' => now(),
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Upload une image pour les patch notes (admin uniquement)
     */
    public function uploadImage(Request $request)
    {
        if (!auth()->user()->is_admin) {
            abort(403);
        }

        $request->validate([
            'image' => 'required|image|max:5120', // max 5MB
        ]);

        $file = $request->file('image');
        $path = $file->store('patch-notes', 'public');
        $url = Storage::url($path);

        return response()->json([
            'success' => true,
            'url' => $url,
        ]);
    }
}
