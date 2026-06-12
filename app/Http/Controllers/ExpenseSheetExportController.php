<?php

namespace App\Http\Controllers;

use App\Models\ExpenseSheet;
use App\Models\ExpenseSheetExport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExpenseSheetExportController extends Controller
{
    public function index()
    {
        $exports = ExpenseSheetExport::orderBy('created_at', 'desc')->get();

        return Inertia::render('expenseSheet/Export/Index', [
            'exports' => $exports,
        ]);
    }

    /**
     * Export the expense sheets total to a CSV file.
     */
    public function export(Request $request)
    {
        // Vérifie que l'utilisateur à la permission d'exporter
        if (! auth()->user()->can('export', ExpenseSheet::class)) {
            abort(403);
        }

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate = Carbon::parse($validated['end_date'])->endOfDay();

        // Petite validation supplémentaire : début <= fin
        if ($startDate->gt($endDate)) {
            return back()->withErrors(['end_date' => 'La date de fin doit être postérieure à la date de début.']);
        }

        $users = User::whereHas('expenseSheets', function ($q) use ($startDate, $endDate) {
            $q->where('approved', true)
                ->whereBetween('validated_at', [$startDate, $endDate]);
        })
            ->with([
                'expenseSheets' => function ($q) use ($startDate, $endDate) {
                    $q->where('approved', true)
                        ->whereBetween('validated_at', [$startDate, $endDate]);
                },
                'expenseSheets.expenseSheetCosts.formCost.form',
            ])->get();

        // Préparer entêtes dynamiques regroupées par mois (selon la date du coût) :
        // pour chaque mois, on collecte les types de coûts utilisés par au moins un agent.
        // $months['Y-m'] = ['label' => 'AVRIL 2026', 'costTypes' => [key => type]]
        $months = [];

        foreach ($users as $user) {
            foreach ($user->expenseSheets as $expenseSheet) {
                foreach ($expenseSheet->expenseSheetCosts as $cost) {
                    if (empty($cost->date)) {
                        continue;
                    }

                    $date = Carbon::parse($cost->date);
                    $monthKey = $date->format('Y-m');

                    if (! isset($months[$monthKey])) {
                        $months[$monthKey] = [
                            'label' => mb_strtoupper($date->locale('fr')->translatedFormat('F Y')),
                            'costTypes' => [],
                        ];
                    }

                    $typePrefix = strtolower($cost->formCost->type) === 'km' ? 'KM' : 'EURO';
                    $key = $typePrefix.' - '.$cost->formCost->name.' ('.$cost->formCost->form->name.')';

                    if (! isset($months[$monthKey]['costTypes'][$key])) {
                        $months[$monthKey]['costTypes'][$key] = $cost->formCost->type;
                    }
                }
            }
        }

        // Trier les mois chronologiquement
        ksort($months);

        // Construire les entêtes : Username, puis pour chaque mois une colonne titre
        // (vide dans les lignes) suivie des colonnes de coûts du mois.
        $headers = ['Username'];
        foreach ($months as $month) {
            $headers[] = $month['label'];
            foreach (array_keys($month['costTypes']) as $key) {
                $headers[] = $key;
            }
        }

        // Construire les lignes : total par utilisateur, par mois et par coût.
        $data = [];
        foreach ($users as $user) {
            // $sums['Y-m'][key] = total
            $sums = [];
            foreach ($months as $monthKey => $month) {
                $sums[$monthKey] = array_fill_keys(array_keys($month['costTypes']), 0);
            }

            foreach ($user->expenseSheets as $expenseSheet) {
                foreach ($expenseSheet->expenseSheetCosts as $cost) {
                    if (empty($cost->date)) {
                        continue;
                    }

                    $monthKey = Carbon::parse($cost->date)->format('Y-m');
                    $typePrefix = strtolower($cost->formCost->type) === 'km' ? 'KM' : 'EURO';
                    $key = $typePrefix.' - '.$cost->formCost->name.' ('.$cost->formCost->form->name.')';

                    if (isset($sums[$monthKey][$key])) {
                        // Si c'est un coût de type KM, on ajoute la distance arrondie
                        if (strtolower($cost->formCost->type) === 'km') {
                            $sums[$monthKey][$key] += round($cost->google_distance);
                        } else {
                            // Sinon, on ajoute le montant en euros
                            $sums[$monthKey][$key] += (float) $cost->amount;
                        }
                    }
                }
            }

            // Vérifier si l'utilisateur a au moins un montant non-nul
            $hasNonZeroAmount = false;
            foreach ($sums as $monthSums) {
                foreach ($monthSums as $sum) {
                    if ($sum > 0) {
                        $hasNonZeroAmount = true;
                        break 2;
                    }
                }
            }

            // N'ajouter la ligne que si l'utilisateur a au moins un montant
            if ($hasNonZeroAmount) {
                $row = [$user->name];
                foreach ($months as $monthKey => $month) {
                    $row[] = ''; // colonne titre du mois, vide dans les lignes
                    foreach (array_keys($month['costTypes']) as $key) {
                        $row[] = $sums[$monthKey][$key];
                    }
                }
                $data[] = $row;
            }
        }

        // Générer Excel en mémoire (fichier temporaire)
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Entêtes
        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValueByColumnAndRow($col, 1, $header);
            $col++;
        }

        // Données
        $rowNumber = 2;
        foreach ($data as $rowData) {
            $col = 1;
            foreach ($rowData as $cell) {
                $sheet->setCellValueByColumnAndRow($col, $rowNumber, $cell);
                $col++;
            }
            $rowNumber++;
        }

        // Nom et chemins
        $fileName = 'export_expense_sheets_'
            .$startDate->format('Ymd')
            .'_au_'
            .$endDate->format('Ymd')
            .'_'.now()->format('His')
            .'.xlsx';

        // On enregistre d’abord dans un tmp local…
        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'exp_');
        $writer->save($tempFile);

        // …puis on copie le fichier vers le storage (ex. disk local) sous /exports
        $relativePath = 'exports/'.$fileName; // <-- sera stocké en DB
        Storage::put($relativePath, file_get_contents($tempFile));

        // 🆕 1) Créer l'export en "pending" (sans file_path au départ)
        $export = ExpenseSheetExport::create([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'completed',
            'file_path' => null,
            'created_by_id' => auth()->id(),
        ]);

        // 🆕 2) Récupérer les IDs des notes de frais comprises dans la période et approuvées
        $expenseSheetIds = ExpenseSheet::query()
            ->where('approved', true)
            ->whereBetween('validated_at', [$startDate, $endDate])
            ->pluck('id')
            ->all();

        // 🆕 3) Synchroniser sur la table pivot
        $export->expenseSheets()->sync($expenseSheetIds);

        // Enfin on propose le téléchargement à l’utilisateur
        // (en lisant depuis le storage pour être cohérent)
        $absolutePath = Storage::path($relativePath);

        $export->update(['file_path' => $relativePath]);

        return redirect()->back()->with('success', 'Export généré avec succès. Vous pouvez le télécharger ci-dessous.');
    }
}
