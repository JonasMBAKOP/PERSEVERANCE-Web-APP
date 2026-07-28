<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Backwards-compatible entry point for the primary-only structure.
 */
class LevelSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SectionsAndLevelsSeeder::class);
    }
}