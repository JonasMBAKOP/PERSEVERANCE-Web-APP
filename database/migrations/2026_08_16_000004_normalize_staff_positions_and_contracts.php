<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->replacePosition('censeur', 'prefet_des_etudes');
        $this->replacePosition('fondateur', 'directeur');

        DB::table('staff')
            ->where('contract_type', 'stagiaire')
            ->update(['contract_type' => 'semi_permanent']);

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE staff MODIFY contract_type ENUM('permanent', 'vacataire', 'semi_permanent') NOT NULL DEFAULT 'permanent'"
            );
        }
    }

    public function down(): void
    {
        DB::table('staff')
            ->where('contract_type', 'semi_permanent')
            ->update(['contract_type' => 'stagiaire']);

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE staff MODIFY contract_type ENUM('permanent', 'vacataire', 'stagiaire') NOT NULL DEFAULT 'permanent'"
            );
        }
    }

    private function replacePosition(string $legacyPosition, string $canonicalPosition): void
    {
        DB::table('staff_positions')
            ->where('position', $legacyPosition)
            ->orderBy('id')
            ->get()
            ->each(function (object $position) use ($legacyPosition, $canonicalPosition) {
                $hasCanonicalPosition = DB::table('staff_positions')
                    ->where('staff_id', $position->staff_id)
                    ->where('position', $canonicalPosition)
                    ->exists();

                if ($hasCanonicalPosition) {
                    DB::table('staff_positions')->where('id', $position->id)->delete();

                    return;
                }

                DB::table('staff_positions')
                    ->where('id', $position->id)
                    ->update(['position' => $canonicalPosition]);
            });
    }
};
