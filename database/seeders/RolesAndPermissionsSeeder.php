<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Réinitialiser le cache des permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ── DÉFINITION DES PERMISSIONS ────────────────────────────────────────
        $permissions = [
            // Utilisateurs & Comptes
            'view-users', 'manage-users',
            // Personnel
            'view-staff', 'manage-staff',
            'view-health', 'manage-health',
            // Élèves
            'view-students', 'manage-students',
            // Inscriptions
            'view-enrollments', 'manage-enrollments',
            // Classes
            'view-classes', 'manage-classes',
            // Matières
            'view-subjects', 'manage-subjects',
            // Notes
            'view-grades', 'enter-grades',
            'validate-grades', 'lock-grades',
            // Bulletins
            'view-bulletins', 'manage-bulletins', 'generate-bulletins', 'print-bulletins',
            // Absences
            'view-absences', 'manage-absences',
            // Finances
            'view-finances', 'manage-finances', 'configure-fees',
            // Discipline
            'view-discipline', 'manage-discipline',
            // Emploi du temps
            'view-timetable', 'manage-timetable',
            // Annonces & Messagerie
            'view-announcements', 'manage-announcements', 'manage-parent-communication',
            'view-messages', 'send-messages',
            // Rapports
            'view-reports', 'export-reports',
            // Paramètres
            'view-settings', 'manage-settings',
            // Années scolaires
            'view-academic-years', 'manage-academic-years',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ── CRÉATION DES RÔLES ET ASSIGNATION DES PERMISSIONS ────────────────

        // 1. SUPER ADMIN — Tout accès (bypass automatique dans Laravel)
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        // Le super-admin bypasse toutes les vérifications via Gate::before()

        // 2. DIRECTEUR / PRINCIPAL / FONDATEUR
        $directeur = Role::firstOrCreate(['name' => 'directeur']);
        $directeur->syncPermissions(Permission::all());

        // 3. CENSEUR / PRÉFET DES ÉTUDES
        $censeur = Role::firstOrCreate(['name' => 'censeur']);
        $censeur->syncPermissions([
            'view-users', 'manage-users',
            'view-staff', 'manage-staff',
            'view-students', 'manage-students',
            'view-enrollments', 'manage-enrollments',
            'view-classes', 'manage-classes',
            'view-subjects', 'manage-subjects',
            'view-grades', 'enter-grades', 'validate-grades', 'lock-grades',
            'view-bulletins', 'manage-bulletins', 'generate-bulletins', 'print-bulletins',
            'view-absences', 'manage-absences',
            'view-discipline', 'manage-discipline',
            'view-timetable', 'manage-timetable',
            'view-announcements', 'manage-announcements', 'manage-parent-communication',
            'view-messages', 'send-messages',
            'view-reports', 'export-reports',
            // 'view-academic-years', 'manage-academic-years',
        ]);

        // 4. ÉCONOME
        $econome = Role::firstOrCreate(['name' => 'econome']);
        $econome->syncPermissions([
            'view-students', 'manage-students',
            'view-enrollments', 'manage-enrollments',
            'view-finances', 'manage-finances', 'configure-fees',
            'view-announcements',
            'view-messages', 'send-messages',
            'view-reports', 'export-reports',
            'view-staff', 'manage-staff',
            'view-grades', 'enter-grades',
            'view-bulletins', 'manage-bulletins',
        ]);

        // 5. ENSEIGNANT
        $enseignant = Role::firstOrCreate(['name' => 'enseignant']);
        $enseignant->syncPermissions([
            'view-students',
            'view-classes',
            'view-subjects',
            'view-grades', 'enter-grades',
            'view-bulletins',
            'view-absences',
            'view-timetable',
            'view-announcements',
            'view-messages', 'send-messages',
        ]);

        // 6. SURVEILLANT GÉNÉRAL
        $surveillant = Role::firstOrCreate(['name' => 'surveillant-general']);
        $surveillant->syncPermissions([
            'view-students', 'manage-students',
            'view-classes', 'manage-classes',
            'view-subjects',
            'view-grades', 'enter-grades',
            'view-bulletins', 'manage-bulletins',
            'view-enrollments',
            'view-absences', 'manage-absences',
            'view-discipline', 'manage-discipline',
            'view-timetable',
            'view-announcements',
            'view-messages', 'send-messages',
            'view-reports',
        ]);

        // 7. SURVEILLANT DE SECTEUR
        $surveillantSecteur = Role::firstOrCreate(['name' => 'surveillant-de-secteur']);
        $surveillantSecteur->syncPermissions([
            'view-students',
            'view-classes',
            'view-absences', 'manage-absences',
            'view-discipline', 'manage-discipline',
            'view-timetable',
            'view-announcements',
            'view-messages', 'send-messages',
            'view-reports'
        ]);

        // 8. SECRÉTAIRE
        $secretaire = Role::firstOrCreate(['name' => 'secretaire']);
        $secretaire->syncPermissions([
            'view-students',
            'view-classes',
            // 'view-absences',
            'view-subjects',
            'view-grades', 'enter-grades',
            'view-bulletins', 'manage-bulletins',
            'view-timetable',
            'view-announcements',
            'view-messages', 'send-messages',
        ]);

        // 9. INFIRMIER(E)
        $infirmier = Role::firstOrCreate(['name' => 'infirmier']);
        $infirmier->syncPermissions([
            'view-students',
            'view-health', 'manage-health',
            'view-announcements',
            'view-messages', 'send-messages',
        ]);

        $this->command->info('✅ Rôles et permissions créés avec succès.');
    }
}
