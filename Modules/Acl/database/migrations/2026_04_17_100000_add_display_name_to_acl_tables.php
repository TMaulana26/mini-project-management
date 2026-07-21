<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');

        if (empty($tableNames)) {
            return;
        }

        // Add display_name to roles
        Schema::table($tableNames['roles'], function (Blueprint $table) {
            $table->json('display_name')->nullable()->after('name');
        });

        // Add display_name to permissions
        Schema::table($tableNames['permissions'], function (Blueprint $table) {
            $table->json('display_name')->nullable()->after('name');
        });

        // Migrate existing names to display_names
        $roles = DB::table($tableNames['roles'])->get();
        foreach ($roles as $role) {
            DB::table($tableNames['roles'])->where('id', $role->id)->update([
                'display_name' => json_encode(['en' => ucwords(str_replace(['-', '_'], ' ', $role->name))]),
            ]);
        }

        $permissions = DB::table($tableNames['permissions'])->get();
        foreach ($permissions as $permission) {
            DB::table($tableNames['permissions'])->where('id', $permission->id)->update([
                'display_name' => json_encode(['en' => ucwords(str_replace(['-', '_'], ' ', $permission->name))]),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');

        if (empty($tableNames)) {
            return;
        }

        Schema::table($tableNames['roles'], function (Blueprint $table) {
            $table->dropColumn('display_name');
        });

        Schema::table($tableNames['permissions'], function (Blueprint $table) {
            $table->dropColumn('display_name');
        });
    }
};
