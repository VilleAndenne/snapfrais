<?php

namespace App\Jobs;

use App\Models\ExpenseSheet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SendDsfReimbursementEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The expense sheet instance.
     *
     * @var ExpenseSheet
     */
    protected $expenseSheet;

    /**
     * The PDF file path.
     *
     * @var string
     */
    protected $pdfPath;

    /**
     * The attachment count.
     *
     * @var int
     */
    protected $attachmentCount;

    /**
     * The converted attachment count.
     *
     * @var int
     */
    protected $convertedCount;

    /**
     * Create a new job instance.
     *
     * @param ExpenseSheet $expenseSheet
     * @param \Illuminate\Support\Collection $dsfCosts
     * @param string $pdfPath
     * @param array $attachments
     */
    public function __construct(ExpenseSheet $expenseSheet, $dsfCosts, string $pdfPath, array $attachments)
    {
        $this->expenseSheet = $expenseSheet;
        $this->pdfPath = $pdfPath;
        $this->attachmentCount = count($attachments);
        $this->convertedCount = count(array_filter($attachments, fn($a) => $a['is_converted'] ?? false));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $recipientEmail = 'sebastien.merveille@ac.andenne.be';

        // Charger les relations nécessaires pour la vue email
        $this->expenseSheet->load([
            'costs.formCost',
            'user',
            'department',
            'validatedBy',
            'form'
        ]);

        // Recharger les coûts DSF avec leurs relations
        $dsfCosts = $this->expenseSheet->costs->filter(function ($cost) {
            return $cost->formCost->processing_department === 'DSF';
        });

        Mail::send('emails.dsf-reimbursement', [
            'expenseSheet' => $this->expenseSheet,
            'dsfCosts' => $dsfCosts,
            'attachmentCount' => $this->attachmentCount,
            'convertedCount' => $this->convertedCount,
        ], function ($message) use ($recipientEmail) {
            $message->to($recipientEmail)
                ->subject('Demande de remboursement DSF - Note de frais #' . $this->expenseSheet->id)
                ->attach(Storage::path($this->pdfPath));
        });
    }
}