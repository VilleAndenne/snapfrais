<?php

namespace App\Http\Controllers;

use App\Models\ExpenseSheet;
use App\Models\ExpenseSheetCost;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StatisticsController extends Controller
{
    /**
     * Afficher la page de statistiques globales (admin uniquement).
     */
    public function index(Request $request): Response
    {
        if (! auth()->user()->is_admin) {
            abort(403);
        }

        $year = (int) $request->input('year', now()->year);

        $availableYears = ExpenseSheet::query()
            ->pluck('created_at')
            ->map(fn ($date) => (int) $date->year)
            ->unique()
            ->sortDesc()
            ->values()
            ->toArray();

        if (! in_array(now()->year, $availableYears, true)) {
            array_unshift($availableYears, now()->year);
        }

        $availableYears = array_values(array_unique($availableYears));
        rsort($availableYears);

        $yearQuery = fn () => ExpenseSheet::query()->whereYear('created_at', $year);

        $totalSheets = (clone $yearQuery())->count();
        $approvedSheets = (clone $yearQuery())->where('approved', true)->count();
        $refusedSheets = (clone $yearQuery())->where('approved', false)->count();
        $pendingSheets = (clone $yearQuery())
            ->whereNull('approved')
            ->where('is_draft', false)
            ->count();
        $draftSheets = (clone $yearQuery())->where('is_draft', true)->count();

        $totalApprovedAmount = (float) (clone $yearQuery())
            ->where('approved', true)
            ->sum('total');

        $averageApprovedAmount = $approvedSheets > 0
            ? round($totalApprovedAmount / $approvedSheets, 2)
            : 0.0;

        $approvalRate = $totalSheets > 0
            ? round(($approvedSheets / $totalSheets) * 100, 1)
            : 0.0;

        $kmCosts = ExpenseSheetCost::query()
            ->whereHas('expenseSheet', function ($query) use ($year) {
                $query->whereYear('created_at', $year)
                    ->where('approved', true);
            })
            ->where('type', 'km')
            ->get(['distance', 'requirements']);

        $kmByTransport = $kmCosts
            ->groupBy(fn ($cost) => $cost->requirements['transport'] ?? 'car')
            ->map(fn ($group) => round((float) $group->sum('distance'), 2))
            ->toArray();

        foreach (['car', 'bike'] as $transport) {
            if (! isset($kmByTransport[$transport])) {
                $kmByTransport[$transport] = 0.0;
            }
        }

        $totalApprovedKm = round((float) array_sum($kmByTransport), 2);

        $monthlyStats = $this->buildMonthlyStats($year);

        $byDepartment = ExpenseSheet::query()
            ->selectRaw('department_id, COUNT(*) as sheets_count, SUM(total) as total_amount')
            ->whereYear('created_at', $year)
            ->where('approved', true)
            ->whereNotNull('department_id')
            ->groupBy('department_id')
            ->with('department:id,name')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'department' => $row->department?->name ?? 'Inconnu',
                'sheets_count' => (int) $row->sheets_count,
                'total_amount' => round((float) $row->total_amount, 2),
            ])
            ->values();

        $byForm = ExpenseSheet::query()
            ->selectRaw('form_id, COUNT(*) as sheets_count, SUM(total) as total_amount')
            ->whereYear('created_at', $year)
            ->where('approved', true)
            ->groupBy('form_id')
            ->with('form:id,name')
            ->orderByDesc('total_amount')
            ->get()
            ->map(fn ($row) => [
                'form' => $row->form?->name ?? 'Inconnu',
                'sheets_count' => (int) $row->sheets_count,
                'total_amount' => round((float) $row->total_amount, 2),
            ])
            ->values();

        $topUsers = ExpenseSheet::query()
            ->selectRaw('user_id, COUNT(*) as sheets_count, SUM(total) as total_amount')
            ->whereYear('created_at', $year)
            ->where('approved', true)
            ->groupBy('user_id')
            ->with('user:id,name')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'user' => $row->user?->name ?? 'Inconnu',
                'sheets_count' => (int) $row->sheets_count,
                'total_amount' => round((float) $row->total_amount, 2),
            ])
            ->values();

        return Inertia::render('admin/Statistics/Index', [
            'selectedYear' => $year,
            'availableYears' => $availableYears,
            'kpis' => [
                'totalSheets' => $totalSheets,
                'approvedSheets' => $approvedSheets,
                'refusedSheets' => $refusedSheets,
                'pendingSheets' => $pendingSheets,
                'draftSheets' => $draftSheets,
                'totalApprovedAmount' => round($totalApprovedAmount, 2),
                'averageApprovedAmount' => $averageApprovedAmount,
                'approvalRate' => $approvalRate,
                'totalApprovedKm' => $totalApprovedKm,
                'kmByTransport' => $kmByTransport,
            ],
            'monthlyStats' => $monthlyStats,
            'byDepartment' => $byDepartment,
            'byForm' => $byForm,
            'topUsers' => $topUsers,
        ]);
    }

    /**
     * Construit les statistiques mensuelles (12 mois) pour l'année donnée.
     *
     * @return array<int, array{month: int, label: string, sheets_count: int, total_amount: float}>
     */
    private function buildMonthlyStats(int $year): array
    {
        $sheets = ExpenseSheet::query()
            ->whereYear('created_at', $year)
            ->where('approved', true)
            ->get(['created_at', 'total']);

        $grouped = $sheets->groupBy(fn ($sheet) => (int) $sheet->created_at->month);

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $group = $grouped->get($m);
            $months[] = [
                'month' => $m,
                'label' => Carbon::create($year, $m, 1)->locale('fr')->isoFormat('MMM'),
                'sheets_count' => $group ? $group->count() : 0,
                'total_amount' => $group ? round((float) $group->sum('total'), 2) : 0.0,
            ];
        }

        return $months;
    }
}
