<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Align pre-existing linked account and staff photo references. */
    public function up(): void
    {
        DB::table('staff')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->select(['id', 'user_id', 'photo'])
            ->each(function (object $staff): void {
                $user = DB::table('users')->select(['id', 'photo'])->find($staff->user_id);

                if (! $user) {
                    return;
                }

                $sharedPhotoPath = $staff->photo ?: $user->photo;
                if (! $sharedPhotoPath) {
                    return;
                }

                DB::table('staff')->where('id', $staff->id)->update(['photo' => $sharedPhotoPath]);
                DB::table('users')->where('id', $user->id)->update(['photo' => $sharedPhotoPath]);
            });
    }

    public function down(): void
    {
        // The common reference is deliberately retained on rollback.
    }
};
