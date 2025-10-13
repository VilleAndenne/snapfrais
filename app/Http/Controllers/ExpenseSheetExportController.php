<?php

namespace App\Http\Controllers;

use App\Models\ExpenseSheet;
use App\Models\ExpenseSheetExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Storage;

class ExpenseSheetExportController extends Controller
{
    public function index() {
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
        if (!auth()->user()->can('export', ExpenseSheet::class)) {
            abort(403);
        }

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date',
        ]);

        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate   = Carbon::parse($validated['end_date'])->endOfDay();

        // Petite validation supplémentaire : début <= fin
        if ($startDate->gt($endDate)) {
            return back()->withErrors(['end_date' => 'La date de fin doit être postérieure à la date de début.']);
        }

        $users = \App\Models\User::whereHas('expenseSheets', function ($q) use ($startDate, $endDate) {
            $q->where('approved', true)
                ->whereBetween('validated_at', [$startDate, $endDate]);
        })
            ->with([
                'expenseSheets' => function ($q) use ($startDate, $endDate) {
                    $q->where('approved', true)
                        ->whereBetween('validated_at', [$startDate, $endDate]);
                },
                'expenseSheets.expenseSheetCosts.formCost.form'
            ])->get();

        // Préparer entêtes dynamiques
        $headers = ['Username'];
        $costTypes = [];

        foreach ($users as $user) {
            foreach ($user->expenseSheets as $expenseSheet) {
                foreach ($expenseSheet->expenseSheetCosts as $cost) {
                    // Déterminer le préfixe selon le type
                    $typePrefix = strtolower($cost->formCost->type) === 'km' ? 'KM' : 'EURO';
                    $key = $typePrefix.' - '.$cost->formCost->name.' ('.$cost->formCost->form->name.')';
                    $costTypes[$key] = $cost->formCost->type;
                }
            }
        }

        $headers = array_merge($headers, array_keys($costTypes));

        // Construire les lignes
        $data = [];
        foreach ($users as $user) {
            $row = [$user->name];
            $costSums = array_fill_keys(array_keys($costTypes), 0);

            foreach ($user->expenseSheets as $expenseSheet) {
                foreach ($expenseSheet->expenseSheetCosts as $cost) {
                    // Utiliser la même logique de clé qu'au-dessus
                    $typePrefix = strtolower($cost->formCost->type) === 'km' ? 'KM' : 'EURO';
                    $key = $typePrefix.' - '.$cost->formCost->name.' ('.$cost->formCost->form->name.')';

                    if (isset($costSums[$key])) {
                        $amount = (float) $cost->amount;
                        $costSums[$key] += $amount;
                    }
                }
            }

            foreach (array_keys($costTypes) as $key) {
                $row[] = $costSums[$key];
            }

            $data[] = $row;
        }

        // Générer Excel en mémoire (fichier temporaire)
        $spreadsheet = new Spreadsheet();
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
            'end_date'   => $endDate,
            'status'     => 'completed',
            'file_path'  => null,
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
