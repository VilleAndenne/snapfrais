<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    /**
     * Crée les organisations Ville et CPAS (idempotent).
     */
    public function run(): void
    {
        $organizations = [
            ['slug' => 'ville', 'name' => 'Ville', 'organization_name' => "Ville d'ANDENNE"],
            ['slug' => 'cpas', 'name' => 'CPAS', 'organization_name' => "CPAS d'ANDENNE"],
        ];

        foreach ($organizations as $organization) {
            Organization::updateOrCreate(
                ['slug' => $organization['slug']],
                [
                    'name' => $organization['name'],
                    'organization_name' => $organization['organization_name'],
                ],
            );
        }
    }
}
