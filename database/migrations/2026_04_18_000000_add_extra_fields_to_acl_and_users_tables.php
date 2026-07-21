<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');

        // Add softDeletes and is_active to users
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('password');
            }
            if (! Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        // Add softDeletes and is_active to roles
        if (! empty($tableNames['roles'])) {
            Schema::table($tableNames['roles'], function (Blueprint $table) {
                if (! Schema::hasColumn($table->getTable(), 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('name');
                }
                if (! Schema::hasColumn($table->getTable(), 'deleted_at')) {
                    $table->softDeletes()->after('updated_at');
                }
            });
        }

        // Add softDeletes, is_active, and menu to permissions
        if (! empty($tableNames['permissions'])) {
            Schema::table($tableNames['permissions'], function (Blueprint $table) {
                if (! Schema::hasColumn($table->getTable(), 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('name');
                }
                if (! Schema::hasColumn($table->getTable(), 'menu')) {
                    $table->json('menu')->nullable()->after('is_active');
                }
                if (! Schema::hasColumn($table->getTable(), 'deleted_at')) {
                    $table->softDeletes()->after('updated_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'deleted_at']);
        });

        if (! empty($tableNames['roles'])) {
            Schema::table($tableNames['roles'], function (Blueprint $table) {
                $table->dropColumn(['is_active', 'deleted_at']);
            });
        }

        if (! empty($tableNames['permissions'])) {
            Schema::table($tableNames['permissions'], function (Blueprint $table) {
                $table->dropColumn(['is_active', 'menu', 'deleted_at']);
            });
        }
    }
};
