<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\ExpenseSheet;
use App\Models\ExpenseSheetExport;
use App\Models\Form;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('organizations:backfill {slug}')]
#[Description('Rattache toutes les données non affectées (et tous les utilisateurs) à une organisation.')]
class BackfillOrganization extends Command
{
    /**
     * Execute the console command.
     *
     * Idempotent : n'affecte que les lignes dont organization_id est null, et
     * attache chaque utilisateur à l'organisation sans créer de doublon.
     */
    public function handle(): int
    {
        $slug = (string) $this->argument('slug');

        $organization = Organization::where('slug', $slug)->first();

        if ($organization === null) {
            $this->error("Aucune organisation avec le slug « {$slug} ».");

            return self::FAILURE;
        }

        foreach ([Department::class, Form::class, ExpenseSheet::class, ExpenseSheetExport::class] as $model) {
            $updated = $model::withoutGlobalScopes()
                ->whereNull('organization_id')
                ->update(['organization_id' => $organization->getKey()]);

            $this->line(sprintf('%s : %d ligne(s) rattachée(s).', class_basename($model), $updated));
        }

        $attached = 0;
        User::query()->select('id')->chunkById(500, function ($users) use ($organization, &$attached): void {
            foreach ($users as $user) {
                $user->organizations()->syncWithoutDetaching([$organization->getKey()]);
                $attached++;
            }
        });

        $this->line("Utilisateurs rattachés : {$attached}.");
        $this->info("Backfill terminé pour « {$organization->name} ».");

        return self::SUCCESS;
    }
}
