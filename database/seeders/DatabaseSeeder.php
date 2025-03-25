<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $user = User::create([
            'name' => 'Test User',
            'email' => 'admin@ac.andenne.be',
            'password' => bcrypt('password'),
        ]);

        $orga = Organization::create([
            'name' => 'Ville d\'Andenne',
            'slug' => 'ville-andenne',
        ]);

        $user->organizations()->attach($orga->id);
    }
}
