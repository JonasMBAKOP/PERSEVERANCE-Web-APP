<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SchoolSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Paramètres principaux de l'établissement
        DB::table('school_settings')->insertOrIgnore([
            'full_name'   => 'ECOLE PRIVEE LAÏC BILINGUE LA PERSEVERANCE PLUS',
            'short_name'  => 'PERSEVERANCE PLUS',
            'logo'        => null,
            'address'     => 'Douala, Cameroun',
            'postal_box'  => '11568 Douala-Cameroun',
            'city'        => 'Douala',
            'region'      => 'Littoral',
            'email'       => null,
            'website'     => null,
            'motto'       => null,
            'order_type'  => 'Privé Laïc',
            'ministry'    => 'Ministère de l\'Education de Base',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // Numéros d'agrément
        DB::table('school_agreements')->insertOrIgnore([
            [
                'number'       => '319/11 MINSEC/SG/DESG/SDSEPESG DU 14 DEC 2011',
                'cycle'        => 'premier_cycle',
                'label'        => 'Agrément Premier Cycle (6ème – 3ème)',
                'issued_date'  => null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'number'       => '134/15 MINSEC/SG/DESG/SDSGEPESG',
                'cycle'        => 'second_cycle',
                'label'        => 'Agrément Second Cycle (2nde – Terminale)',
                'issued_date'  => null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ]);

        $this->command->info('✅ Paramètres de l\'établissement créés.');
    }
}
