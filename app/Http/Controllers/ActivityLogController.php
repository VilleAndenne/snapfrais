<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    /**
     * Afficher la liste des logs d'activité (admin uniquement)
     */
    public function index(Request $request)
    {
        if (! auth()->user()->is_admin) {
            abort(403);
        }

        $query = Activity::with(['causer', 'subject'])
            ->orderBy('created_at', 'desc');

        // Filtrer par type de log
        if ($request->filled('log_name')) {
            $query->where('log_name', $request->log_name);
        }

        // Filtrer par utilisateur (causer)
        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->causer_id);
        }

        // Filtrer par type d'événement
        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        // Filtrer par type de sujet
        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->subject_type);
        }

        // Recherche par description
        if ($request->filled('search')) {
            $query->where('description', 'like', '%'.$request->search.'%');
        }

        // Filtrer par date
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $activities = $query->paginate(50)->withQueryString();

        // Récupérer les options de filtres
        $logNames = Activity::select('log_name')
            ->distinct()
            ->pluck('log_name')
            ->sort()
            ->values();

        $subjectTypes = Activity::select('subject_type')
            ->distinct()
            ->pluck('subject_type')
            ->map(fn ($type) => class_basename($type))
            ->sort()
            ->values();

        $events = Activity::select('event')
            ->distinct()
            ->pluck('event')
            ->sort()
            ->values();

        return Inertia::render('admin/ActivityLog/Index', [
            'activities' => $activities,
            'filters' => $request->only(['log_name', 'causer_id', 'event', 'subject_type', 'search', 'date_from', 'date_to']),
            'logNames' => $logNames,
            'subjectTypes' => $subjectTypes,
            'events' => $events,
        ]);
    }

    /**
     * Afficher les détails d'un log spécifique
     */
    public function show(Activity $activity)
    {
        if (! auth()->user()->is_admin) {
            abort(403);
        }

        $activity->load(['causer', 'subject']);

        return Inertia::render('admin/ActivityLog/Show', [
            'activity' => $activity,
        ]);
    }
}
