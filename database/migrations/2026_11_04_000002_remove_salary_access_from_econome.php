<?php

use Spatie\Permission\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $econome = Role::where('name', 'econome')->first();
        $econome?->revokePermissionTo('manage-staff');
    }

    public function down(): void
    {
        $econome = Role::where('name', 'econome')->first();
        $econome?->givePermissionTo('manage-staff');
    }
};
