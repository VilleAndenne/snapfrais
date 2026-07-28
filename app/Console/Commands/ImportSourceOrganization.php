<?php

namespace App\Console\Commands;

use App\Models\Organization;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

#[Signature('organizations:import-source {slug} {--source=import_source} {--force}')]
#[Description('Importe les données métier d\'une base source (ex. CPAS) dans le socle, rattachées à une organisation, avec remapping des identifiants.')]
class ImportSourceOrganization extends Command
{
    /**
     * Anciens ID (source) -> nouveaux ID (socle), par table.
     *
     * @var array<string, array<int, int>>
     */
    private array $maps = [
        'users' => [],
        'forms' => [],
        'form_costs' => [],
        'departments' => [],
        'expense_sheets' => [],
        'expense_sheet_exports' => [],
    ];

    private int $orgId;

    private string $source;

    /**
     * @var list<string>
     */
    private array $mergedUsers = [];

    /**
     * @var list<string>
     */
    private array $importedSuperAdmins = [];

    public function handle(): int
    {
        $slug = (string) $this->argument('slug');
        $this->source = (string) $this->option('source');

        $organization = Organization::where('slug', $slug)->first();

        if ($organization === null) {
            $this->error("Aucune organisation avec le slug « {$slug} ». Lancez d'abord le seeder.");

            return self::FAILURE;
        }

        $this->orgId = (int) $organization->getKey();

        try {
            DB::connection($this->source)->getPdo();
        } catch (\Throwable $e) {
            $this->error("Connexion source « {$this->source} » inaccessible : ".$e->getMessage());

            return self::FAILURE;
        }

        if (! $this->option('force') && $this->organizationAlreadyPopulated()) {
            $this->error("L'organisation « {$organization->name} » contient déjà des données. Relancez avec --force si l'import précédent a échoué (après restauration du socle).");

            return self::FAILURE;
        }

        $this->info("Import de « {$this->source} » vers l'organisation « {$organization->name} »…");

        DB::transaction(function (): void {
            $this->importUsers();
            $this->importForms();
            $this->importDepartments();
            $this->importExpenseSheets();
            $this->importExports();
        });

        $this->report();

        return self::SUCCESS;
    }

    private function organizationAlreadyPopulated(): bool
    {
        foreach (['departments', 'forms', 'expense_sheets', 'expense_sheet_exports'] as $table) {
            if (DB::table($table)->where('organization_id', $this->orgId)->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fusionne par email : réutilise le compte existant du socle, sinon le crée.
     */
    private function importUsers(): void
    {
        foreach ($this->sourceRows('users') as $row) {
            $data = (array) $row;
            $oldId = (int) $data['id'];
            $email = strtolower((string) $data['email']);

            $existing = DB::table('users')->whereRaw('lower(email) = ?', [$email])->first();

            if ($existing !== null) {
                $this->maps['users'][$oldId] = (int) $existing->id;
                $this->mergedUsers[] = $email;
            } else {
                unset($data['id']);
                $newId = (int) DB::table('users')->insertGetId($data);
                $this->maps['users'][$oldId] = $newId;

                if (! empty($data['super_admin'])) {
                    $this->importedSuperAdmins[] = $email;
                }
            }

            DB::table('organization_user')->insertOrIgnore([
                'organization_id' => $this->orgId,
                'user_id' => $this->maps['users'][$oldId],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }

    private function importForms(): void
    {
        $this->copyRows('forms', [], 'forms', assignOrg: true);
        $this->copyRows('form_requirements', ['form_id' => 'forms']);
        $this->copyRows('form_costs', ['form_id' => 'forms'], 'form_costs');
        $this->copyRows('form_cost_remboursiement_rates', ['form_cost_id' => 'form_costs']);
        $this->copyRows('form_cost_requirements', ['form_cost_id' => 'form_costs']);
    }

    /**
     * Deux passes pour l'auto-référence parent_id.
     */
    private function importDepartments(): void
    {
        $parents = [];

        foreach ($this->sourceRows('departments') as $row) {
            $data = (array) $row;
            $oldId = (int) $data['id'];
            $parents[$oldId] = $data['parent_id'] !== null ? (int) $data['parent_id'] : null;

            unset($data['id']);
            $data['parent_id'] = null;
            $data['organization_id'] = $this->orgId;

            $this->maps['departments'][$oldId] = (int) DB::table('departments')->insertGetId($data);
        }

        foreach ($parents as $oldId => $oldParentId) {
            if ($oldParentId !== null) {
                DB::table('departments')
                    ->where('id', $this->maps['departments'][$oldId])
                    ->update(['parent_id' => $this->mapId('departments', $oldParentId)]);
            }
        }

        $this->copyRows('department_user', ['user_id' => 'users', 'department_id' => 'departments']);
    }

    private function importExpenseSheets(): void
    {
        $this->copyRows('expense_sheets', [
            'form_id' => 'forms',
            'user_id' => 'users',
            'validated_by' => 'users',
            'department_id' => 'departments',
            'created_by' => 'users',
        ], 'expense_sheets', assignOrg: true);

        $this->copyRows('expense_sheet_costs', [
            'expense_sheet_id' => 'expense_sheets',
            'form_cost_id' => 'form_costs',
        ]);
    }

    private function importExports(): void
    {
        $this->copyRows('expense_sheet_exports', ['created_by_id' => 'users'], 'expense_sheet_exports', assignOrg: true);
        $this->copyRows('expense_sheet_export_expense_sheets', [
            'expense_sheet_export_id' => 'expense_sheet_exports',
            'expense_sheet_id' => 'expense_sheets',
        ]);
    }

    /**
     * Copie générique d'une table avec remapping des clés étrangères.
     *
     * @param  array<string, string>  $foreignKeys  colonne => nom de la map
     * @param  string|null  $recordMap  map à alimenter (old id => new id) si la table est référencée
     */
    private function copyRows(string $table, array $foreignKeys, ?string $recordMap = null, bool $assignOrg = false): void
    {
        foreach ($this->sourceRows($table) as $row) {
            $data = (array) $row;
            $oldId = isset($data['id']) ? (int) $data['id'] : null;
            unset($data['id']);

            foreach ($foreignKeys as $column => $mapName) {
                if (($data[$column] ?? null) !== null) {
                    $data[$column] = $this->mapId($mapName, (int) $data[$column]);
                }
            }

            if ($assignOrg) {
                $data['organization_id'] = $this->orgId;
            }

            if ($recordMap !== null && $oldId !== null) {
                $this->maps[$recordMap][$oldId] = (int) DB::table($table)->insertGetId($data);
            } else {
                DB::table($table)->insert($data);
            }
        }
    }

    /**
     * @return iterable<object>
     */
    private function sourceRows(string $table): iterable
    {
        return DB::connection($this->source)->table($table)->orderBy('id')->cursor();
    }

    private function mapId(string $mapName, int $oldId): int
    {
        if (! isset($this->maps[$mapName][$oldId])) {
            throw new \RuntimeException("Référence introuvable : {$mapName} #{$oldId} n'a pas été importé (intégrité source rompue ?).");
        }

        return $this->maps[$mapName][$oldId];
    }

    private function report(): void
    {
        $this->newLine();
        $this->info('Import terminé.');
        $this->table(['Table', 'Importées'], collect([
            'users' => count($this->maps['users']),
            'forms' => count($this->maps['forms']),
            'form_costs' => count($this->maps['form_costs']),
            'departments' => count($this->maps['departments']),
            'expense_sheets' => count($this->maps['expense_sheets']),
            'expense_sheet_exports' => count($this->maps['expense_sheet_exports']),
        ])->map(fn ($count, $table) => [$table, $count])->values()->all());

        if ($this->mergedUsers !== []) {
            $this->warn(count($this->mergedUsers).' utilisateur(s) fusionné(s) par email (compte du socle réutilisé) : '.implode(', ', $this->mergedUsers));
        }

        if ($this->importedSuperAdmins !== []) {
            $this->warn('⚠ Super-admins importés (pouvoir transverse à toutes les orgs) — à vérifier : '.implode(', ', $this->importedSuperAdmins));
        }
    }
}
