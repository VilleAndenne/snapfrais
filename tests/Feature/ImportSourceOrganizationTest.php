<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ExpenseSheet;
use App\Models\Form;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ImportSourceOrganizationTest extends TestCase
{
    use RefreshDatabase;

    private string $sourceDb;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sourceDb = tempnam(sys_get_temp_dir(), 'cpas_src_');

        config()->set('database.connections.import_source', [
            'driver' => 'sqlite',
            'database' => $this->sourceDb,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);

        DB::purge('import_source');
        $this->createSourceSchema();
    }

    /**
     * Minimal source schema (domain tables only), mimicking a copy of the CPAS DB.
     */
    private function createSourceSchema(): void
    {
        $schema = Schema::connection('import_source');

        $schema->create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email');
            $t->string('password');
            $t->boolean('is_admin')->default(false);
            $t->boolean('super_admin')->default(false);
            $t->timestamps();
        });
        $schema->create('forms', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->text('description')->nullable();
            $t->timestamps();
        });
        $schema->create('form_requirements', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('form_id');
            $t->string('name')->nullable();
            $t->timestamps();
        });
        $schema->create('form_costs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('form_id');
            $t->string('name');
            $t->text('description')->nullable();
            $t->string('type')->nullable();
            $t->timestamps();
        });
        $schema->create('form_cost_remboursiement_rates', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('form_cost_id');
            $t->date('start_date')->nullable();
            $t->decimal('value', 8, 2)->nullable();
            $t->timestamps();
        });
        $schema->create('form_cost_requirements', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('form_cost_id');
            $t->string('name')->nullable();
            $t->timestamps();
        });
        $schema->create('departments', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->unsignedBigInteger('parent_id')->nullable();
            $t->timestamps();
        });
        $schema->create('department_user', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('department_id');
            $t->unsignedBigInteger('user_id');
            $t->boolean('is_head')->default(false);
            $t->timestamps();
        });
        $schema->create('expense_sheets', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('form_id');
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('department_id')->nullable();
            $t->unsignedBigInteger('validated_by')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->string('status')->nullable();
            $t->decimal('total', 10, 2)->nullable();
            $t->boolean('is_draft')->default(false);
            $t->timestamps();
        });
        $schema->create('expense_sheet_costs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('expense_sheet_id');
            $t->unsignedBigInteger('form_cost_id');
            $t->string('type')->nullable();
            $t->decimal('total', 10, 2)->nullable();
            $t->date('date')->nullable();
            $t->timestamps();
        });
        $schema->create('expense_sheet_exports', function (Blueprint $t) {
            $t->id();
            $t->date('start_date')->nullable();
            $t->date('end_date')->nullable();
            $t->string('status')->nullable();
            $t->unsignedBigInteger('created_by_id')->nullable();
            $t->timestamps();
        });
        $schema->create('expense_sheet_export_expense_sheets', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('expense_sheet_export_id');
            $t->unsignedBigInteger('expense_sheet_id');
            $t->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::purge('import_source');

        if (isset($this->sourceDb) && file_exists($this->sourceDb)) {
            @unlink($this->sourceDb);
        }

        setCurrentOrganization(null);

        parent::tearDown();
    }

    private function seedSource(): void
    {
        $now = Carbon::parse('2026-01-01 10:00:00');
        $source = DB::connection('import_source');

        $source->table('users')->insert([
            ['id' => 1, 'name' => 'Agent CPAS', 'email' => 'agent@cpas.be', 'password' => 'x', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'Admin Partagé', 'email' => 'admin@ville.be', 'password' => 'x', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $source->table('forms')->insert(['id' => 1, 'name' => 'Frais CPAS', 'description' => 'x', 'created_at' => $now, 'updated_at' => $now]);
        $source->table('form_costs')->insert(['id' => 1, 'form_id' => 1, 'name' => 'KM', 'description' => 'Indemnité', 'type' => 'km', 'created_at' => $now, 'updated_at' => $now]);
        $source->table('form_cost_remboursiement_rates')->insert(['id' => 1, 'form_cost_id' => 1, 'start_date' => '2026-01-01', 'value' => 0.42, 'created_at' => $now, 'updated_at' => $now]);

        $source->table('departments')->insert([
            ['id' => 1, 'name' => 'Direction CPAS', 'parent_id' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'Service Social', 'parent_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
        $source->table('department_user')->insert(['id' => 1, 'department_id' => 2, 'user_id' => 1, 'is_head' => true, 'created_at' => $now, 'updated_at' => $now]);

        $source->table('expense_sheets')->insert([
            'id' => 1, 'form_id' => 1, 'user_id' => 2, 'department_id' => 2, 'created_by' => 1,
            'status' => 'En attente', 'total' => 100, 'is_draft' => false, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $source->table('expense_sheet_costs')->insert([
            'id' => 1, 'expense_sheet_id' => 1, 'form_cost_id' => 1, 'type' => 'km', 'total' => 100, 'date' => '2026-01-02', 'created_at' => $now, 'updated_at' => $now,
        ]);

        $source->table('expense_sheet_exports')->insert(['id' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-01-31', 'status' => 'done', 'created_by_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
        $source->table('expense_sheet_export_expense_sheets')->insert(['id' => 1, 'expense_sheet_export_id' => 1, 'expense_sheet_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
    }

    public function test_it_imports_source_data_with_remapping_and_email_merge(): void
    {
        $ville = Organization::factory()->create(['slug' => 'ville']);
        $cpas = Organization::factory()->create(['slug' => 'cpas']);

        // Compte existant du socle qui partage l'email d'un agent CPAS.
        $shared = User::factory()->create(['email' => 'admin@ville.be']);
        $shared->organizations()->attach($ville);

        $this->seedSource();

        $exitCode = Artisan::call('organizations:import-source', ['slug' => 'cpas', '--source' => 'import_source']);
        $this->assertSame(0, $exitCode);

        // Pas de doublon : l'agent partagé reste un seul compte, dans les DEUX orgs.
        $this->assertSame(2, User::count());
        $this->assertTrue($shared->fresh()->belongsToOrganization($ville));
        $this->assertTrue($shared->fresh()->belongsToOrganization($cpas));

        $agent = User::where('email', 'agent@cpas.be')->firstOrFail();
        $this->assertTrue($agent->belongsToOrganization($cpas));
        $this->assertFalse($agent->belongsToOrganization($ville));

        // Isolation : tout le métier importé est rattaché à l'org CPAS.
        setCurrentOrganization($cpas);
        $this->assertSame(1, Form::count());
        $this->assertSame(2, Department::count());
        $this->assertSame(1, ExpenseSheet::count());

        // L'auto-référence parent_id est correctement remappée.
        $child = Department::where('name', 'Service Social')->firstOrFail();
        $parent = Department::where('name', 'Direction CPAS')->firstOrFail();
        $this->assertSame($parent->id, $child->parent_id);

        // La note de frais pointe vers le compte fusionné (id du socle) et la bonne org.
        $sheet = ExpenseSheet::firstOrFail();
        $this->assertSame($shared->id, $sheet->user_id);
        $this->assertSame($cpas->id, $sheet->organization_id);
        $this->assertSame($child->id, $sheet->department_id);
        $this->assertSame(1, $sheet->costs()->count());

        // Le socle Ville n'a reçu aucune donnée métier.
        setCurrentOrganization($ville);
        $this->assertSame(0, Form::count());
        $this->assertSame(0, ExpenseSheet::count());
    }

    public function test_it_refuses_to_import_twice_without_force(): void
    {
        Organization::factory()->create(['slug' => 'cpas']);
        $this->seedSource();

        $this->assertSame(0, Artisan::call('organizations:import-source', ['slug' => 'cpas', '--source' => 'import_source']));
        $this->assertSame(1, Artisan::call('organizations:import-source', ['slug' => 'cpas', '--source' => 'import_source']));
    }

    public function test_it_fails_for_unknown_slug(): void
    {
        $this->assertSame(1, Artisan::call('organizations:import-source', ['slug' => 'inconnu', '--source' => 'import_source']));
    }
}
