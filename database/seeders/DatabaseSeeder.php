<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call([
            RolesAndPermissionsSeeder::class,   // 1. Rôles et permissions
            SchoolSettingsSeeder::class,         // 2. Paramètres l'etablissement
            SectionsAndLevelsSeeder::class,      // 4. Sections et niveaux
            SubjectCategoriesSeeder::class,      // 5. Catégories de matières
            AppreciationScalesSeeder::class,     // 6. Barème CNA → CTBA
            CouncilDecisionsSeeder::class,       // 7. Décisions du conseil
            DistinctionsSeeder::class,           // 8. Distinctions et sanctions
            AdminUserSeeder::class,              // 9. Compte Super Admin
        ]);
    }
}
