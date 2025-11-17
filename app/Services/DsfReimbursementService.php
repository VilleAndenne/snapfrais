<?php

namespace App\Services;

use App\Jobs\SendDsfReimbursementEmail;
use App\Models\ExpenseSheet;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;

class DsfReimbursementService
{
    /**
     * Génère et envoie un PDF de demande de remboursement pour les coûts DSF
     */
    public function generateAndSendReimbursementPdf(ExpenseSheet $expenseSheet): void
    {
        // Charger les relations nécessaires
        $expenseSheet->load([
            'costs.formCost',
            'user',
            'department',
            'validatedBy',
            'form'
        ]);

        // Filtrer uniquement les coûts DSF
        $dsfCosts = $expenseSheet->costs->filter(function ($cost) {
            return $cost->formCost->processing_department === 'DSF';
        });

        // Si aucun coût DSF, ne rien faire
        if ($dsfCosts->isEmpty()) {
            return;
        }

        // Collecter tous les fichiers annexes des requirements
        $attachments = $this->collectAttachments($dsfCosts, $expenseSheet);

        // Générer le PDF en format paysage
        $pdf = Pdf::loadView('expenseSheet.dsf-reimbursement', [
            'expenseSheet' => $expenseSheet,
            'dsfCosts' => $dsfCosts,
            'attachments' => $attachments,
        ])->setPaper('a4', 'landscape');

        // Créer le nom du fichier
        $fileName = 'demande_remboursement_DSF_' . $expenseSheet->id . '_' . now()->format('YmdHis') . '.pdf';
        $filePath = 'dsf_reimbursements/' . $fileName;

        // Sauvegarder le PDF principal dans le storage temporaire
        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $tempMainPdfPath = $tempDir . '/main_' . $expenseSheet->id . '_' . time() . '.pdf';
        file_put_contents($tempMainPdfPath, $pdf->output());

        \Log::info("PDF principal créé", ['path' => $tempMainPdfPath, 'size' => filesize($tempMainPdfPath)]);

        // Fusionner avec les PDFs annexes si présents
        $finalPdfPath = $this->mergePdfsWithAttachments($tempMainPdfPath, $attachments, $expenseSheet->id);

        // Sauvegarder le PDF final
        if (file_exists($finalPdfPath)) {
            Storage::put($filePath, file_get_contents($finalPdfPath));
            \Log::info("PDF final sauvegardé", ['path' => $filePath, 'size' => filesize($finalPdfPath)]);
        } else {
            \Log::error("Le PDF final n'existe pas", ['expected_path' => $finalPdfPath]);
        }

        // Nettoyer les fichiers temporaires
        if (file_exists($tempMainPdfPath)) @unlink($tempMainPdfPath);
        if (file_exists($finalPdfPath) && $finalPdfPath !== $tempMainPdfPath) @unlink($finalPdfPath);

        // Nettoyer les PDFs convertis temporaires
        foreach ($attachments as $attachment) {
            if (isset($attachment['is_converted']) && $attachment['is_converted'] && file_exists($attachment['path'])) {
                @unlink($attachment['path']);
                \Log::info("Fichier temporaire nettoyé", ['path' => $attachment['path']]);
            }
        }

        // Dispatcher le job pour envoyer l'email de manière asynchrone
        SendDsfReimbursementEmail::dispatch($expenseSheet, $dsfCosts, $filePath, $attachments);
    }

    /**
     * Vérifie si une expense sheet contient des coûts DSF
     */
    public function hasDsfCosts(ExpenseSheet $expenseSheet): bool
    {
        return $expenseSheet->costs()
            ->whereHas('formCost', function ($query) {
                $query->where('processing_department', 'DSF');
            })
            ->exists();
    }

    /**
     * Collecte tous les fichiers annexes des requirements
     */
    private function collectAttachments($dsfCosts, ExpenseSheet $expenseSheet): array
    {
        $attachments = [];

        \Log::info("Début de collecte des attachments", ['costs_count' => $dsfCosts->count()]);

        foreach ($dsfCosts as $cost) {
            \Log::info("Traitement du coût", [
                'cost_id' => $cost->id,
                'cost_name' => $cost->formCost->name ?? 'N/A',
                'requirements_raw' => $cost->requirements
            ]);

            $requirements = is_string($cost->requirements)
                ? json_decode($cost->requirements, true)
                : $cost->requirements;

            if (!is_array($requirements)) {
                \Log::warning("Requirements n'est pas un tableau", ['cost_id' => $cost->id]);
                continue;
            }

            \Log::info("Requirements trouvés", [
                'cost_id' => $cost->id,
                'requirements_count' => count($requirements),
                'requirements' => $requirements
            ]);

            foreach ($requirements as $key => $requirement) {
                if (!is_array($requirement) || !isset($requirement['file'])) {
                    \Log::info("Requirement ignoré (pas de fichier)", ['key' => $key, 'requirement' => $requirement]);
                    continue;
                }

                $fileUrl = $requirement['file'];
                \Log::info("Fichier trouvé", ['key' => $key, 'url' => $fileUrl]);

                // Extraire le chemin relatif depuis l'URL
                // Format attendu: /storage/xxx/yyy.ext
                $relativePath = str_replace('/storage/', '', parse_url($fileUrl, PHP_URL_PATH));
                \Log::info("Chemin extrait", ['url' => $fileUrl, 'relative_path' => $relativePath]);

                // Construire le chemin absolu directement
                $fullPath = storage_path('app/public/' . $relativePath);

                // Vérifier si le fichier existe
                if (!file_exists($fullPath)) {
                    \Log::warning("Fichier n'existe pas", [
                        'relative_path' => $relativePath,
                        'full_path' => $fullPath,
                        'file_exists' => file_exists($fullPath)
                    ]);
                    continue;
                }

                $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                $mimeType = mime_content_type($fullPath);

                \Log::info("Fichier validé", [
                    'path' => $fullPath,
                    'extension' => $extension,
                    'mime' => $mimeType,
                    'size' => filesize($fullPath)
                ]);

                // Vérifier si le type de fichier est supporté
                $supportedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'docx', 'doc'];
                if (!in_array($extension, $supportedExtensions)) {
                    \Log::warning("Type de fichier non supporté", ['extension' => $extension, 'path' => $fullPath]);
                    continue;
                }

                // Convertir en PDF si nécessaire
                $pdfPath = $fullPath;
                $isConverted = false;

                if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'docx', 'doc'])) {
                    \Log::info("Conversion en PDF nécessaire", ['extension' => $extension, 'file' => basename($fullPath)]);
                    $convertedPath = $this->convertToPdf($fullPath, $extension, $expenseSheet->id);

                    if ($convertedPath && file_exists($convertedPath)) {
                        $pdfPath = $convertedPath;
                        $isConverted = true;
                        \Log::info("Fichier converti avec succès", ['original' => basename($fullPath), 'pdf' => basename($pdfPath)]);
                    } else {
                        \Log::warning("Échec de la conversion en PDF", ['file' => basename($fullPath)]);
                        continue;
                    }
                }

                // Ajouter le PDF (original ou converti) à la liste des attachments
                $attachments[] = [
                    'type' => 'pdf',
                    'name' => basename($fullPath),
                    'cost_name' => $cost->formCost->name ?? 'Coût',
                    'path' => $pdfPath,
                    'is_converted' => $isConverted,
                ];
            }
        }

        \Log::info("Collecte terminée", [
            'total_attachments' => count($attachments),
            'pdfs' => count($attachments),
            'converted' => count(array_filter($attachments, fn($a) => $a['is_converted'] ?? false))
        ]);

        return $attachments;
    }

    /**
     * Fusionne le PDF principal avec les PDFs annexes
     */
    private function mergePdfsWithAttachments(string $mainPdfPath, array $attachments, int $expenseSheetId): string
    {
        // Si pas de PDFs à fusionner, retourner le PDF principal
        $pdfAttachments = array_filter($attachments, fn($a) => $a['type'] === 'pdf');

        if (empty($pdfAttachments)) {
            \Log::info("Aucun PDF à fusionner, retour du PDF principal");
            return $mainPdfPath;
        }

        \Log::info("Début de fusion de PDFs", [
            'main_pdf' => $mainPdfPath,
            'annexes_count' => count($pdfAttachments)
        ]);

        $tempDir = storage_path('app/temp');
        $outputPath = $tempDir . '/merged_' . $expenseSheetId . '_' . time() . '.pdf';

        try {
            $fpdi = new Fpdi();

            // Ajouter toutes les pages du PDF principal
            \Log::info("Import du PDF principal");
            $pageCount = $fpdi->setSourceFile($mainPdfPath);
            \Log::info("PDF principal: $pageCount pages");

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $fpdi->importPage($pageNo);
                $size = $fpdi->getTemplateSize($templateId);

                $fpdi->AddPage($size['orientation'] ?? 'L', [$size['width'], $size['height']]);
                $fpdi->useTemplate($templateId);
            }

            // Ajouter les PDFs annexes
            foreach ($pdfAttachments as $index => $attachment) {
                \Log::info("Traitement de l'annexe PDF", [
                    'index' => $index + 1,
                    'name' => $attachment['name'],
                    'path' => $attachment['path']
                ]);

                if (!file_exists($attachment['path'])) {
                    \Log::warning("Fichier annexe introuvable", ['path' => $attachment['path']]);
                    continue;
                }

                try {
                    $annexPageCount = $fpdi->setSourceFile($attachment['path']);
                    \Log::info("Annexe PDF: $annexPageCount pages");

                    for ($pageNo = 1; $pageNo <= $annexPageCount; $pageNo++) {
                        $templateId = $fpdi->importPage($pageNo);
                        $size = $fpdi->getTemplateSize($templateId);

                        // Ajouter directement la page du PDF annexe
                        $fpdi->AddPage($size['orientation'] ?? 'P', [$size['width'], $size['height']]);
                        $fpdi->useTemplate($templateId);
                    }

                    \Log::info("Annexe PDF fusionnée avec succès", ['name' => $attachment['name']]);

                } catch (\Exception $e) {
                    \Log::warning("Erreur lors de la fusion du PDF annexe", [
                        'name' => $attachment['name'],
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    continue;
                }
            }

            // Sauvegarder le PDF fusionné
            $fpdi->Output('F', $outputPath);
            \Log::info("PDF fusionné sauvegardé", [
                'path' => $outputPath,
                'size' => filesize($outputPath)
            ]);

            return $outputPath;

        } catch (\Exception $e) {
            \Log::error("Erreur critique lors de la fusion des PDFs", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // En cas d'erreur, retourner le PDF principal sans fusion
            return $mainPdfPath;
        }
    }

    /**
     * Convertit une image en PDF
     */
    private function convertImageToPdf(string $imagePath, int $expenseSheetId): ?string
    {
        try {
            $tempDir = storage_path('app/temp');
            $outputPath = $tempDir . '/img_' . $expenseSheetId . '_' . time() . '_' . basename($imagePath, pathinfo($imagePath, PATHINFO_EXTENSION)) . '.pdf';

            // Utiliser TCPDF pour créer un PDF avec l'image
            $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

            // Supprimer header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // Ajouter une page
            $pdf->AddPage();

            // Obtenir les dimensions de l'image
            list($width, $height) = getimagesize($imagePath);

            // Calculer les dimensions pour centrer l'image sur la page A4 landscape (297x210mm)
            $pageWidth = 297;
            $pageHeight = 210;
            $margin = 10;

            // Calculer le ratio pour ajuster l'image à la page
            $maxWidth = $pageWidth - (2 * $margin);
            $maxHeight = $pageHeight - (2 * $margin);

            $ratio = min($maxWidth / ($width * 0.264583), $maxHeight / ($height * 0.264583)); // Conversion px to mm
            $imgWidth = ($width * 0.264583) * $ratio;
            $imgHeight = ($height * 0.264583) * $ratio;

            // Centrer l'image
            $x = ($pageWidth - $imgWidth) / 2;
            $y = ($pageHeight - $imgHeight) / 2;

            // Ajouter l'image
            $pdf->Image($imagePath, $x, $y, $imgWidth, $imgHeight, '', '', '', true, 300, '', false, false, 1, false, false, false);

            // Sauvegarder le PDF
            $pdf->Output($outputPath, 'F');

            \Log::info("Image convertie en PDF", ['image' => $imagePath, 'pdf' => $outputPath, 'size' => filesize($outputPath)]);

            return $outputPath;

        } catch (\Exception $e) {
            \Log::error("Erreur lors de la conversion d'image en PDF", [
                'image' => $imagePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Convertit un document Word (.docx ou .doc) en PDF
     */
    private function convertWordToPdf(string $wordPath, int $expenseSheetId): ?string
    {
        try {
            $tempDir = storage_path('app/temp');
            $outputPath = $tempDir . '/word_' . $expenseSheetId . '_' . time() . '_' . basename($wordPath, pathinfo($wordPath, PATHINFO_EXTENSION)) . '.pdf';

            // Configurer PHPWord pour utiliser TCPDF
            Settings::setPdfRendererName(Settings::PDF_RENDERER_TCPDF);
            Settings::setPdfRendererPath(base_path('vendor/tecnickcom/tcpdf'));

            // Charger le document Word
            $phpWord = IOFactory::load($wordPath);

            // Sauvegarder en PDF
            $pdfWriter = IOFactory::createWriter($phpWord, 'PDF');
            $pdfWriter->save($outputPath);

            \Log::info("Document Word converti en PDF", ['word' => $wordPath, 'pdf' => $outputPath, 'size' => filesize($outputPath)]);

            return $outputPath;

        } catch (\Exception $e) {
            \Log::error("Erreur lors de la conversion de Word en PDF", [
                'word' => $wordPath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Convertit un fichier en PDF selon son type
     */
    private function convertToPdf(string $filePath, string $extension, int $expenseSheetId): ?string
    {
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            return $this->convertImageToPdf($filePath, $expenseSheetId);
        } elseif (in_array($extension, ['docx', 'doc'])) {
            return $this->convertWordToPdf($filePath, $expenseSheetId);
        }

        return null;
    }
}
