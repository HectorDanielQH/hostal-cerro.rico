<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('adminlte_roles') && ! Schema::hasColumn('adminlte_roles', 'guard_name')) {
            Schema::table('adminlte_roles', function (Blueprint $table): void {
                $table->string('guard_name')->default('web')->after('name');
            });
        }

        if (Schema::hasTable('adminlte_permissions') && ! Schema::hasColumn('adminlte_permissions', 'guard_name')) {
            Schema::table('adminlte_permissions', function (Blueprint $table): void {
                $table->string('guard_name')->default('web')->after('name');
            });
        }

        if (! Schema::hasTable('adminlte_role_has_permissions')) {
            Schema::create('adminlte_role_has_permissions', function (Blueprint $table): void {
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger('role_id');

                $table->foreign('permission_id')
                    ->references('id')
                    ->on('adminlte_permissions')
                    ->onDelete('cascade');

                $table->foreign('role_id')
                    ->references('id')
                    ->on('adminlte_roles')
                    ->onDelete('cascade');

                $table->primary(['permission_id', 'role_id']);
            });
        }

        if (! Schema::hasTable('adminlte_model_has_roles')) {
            Schema::create('adminlte_model_has_roles', function (Blueprint $table): void {
                $table->unsignedBigInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');

                $table->index(['model_id', 'model_type'], 'adminlte_model_has_roles_model_id_model_type_index');
                $table->foreign('role_id')
                    ->references('id')
                    ->on('adminlte_roles')
                    ->onDelete('cascade');

                $table->primary(['role_id', 'model_id', 'model_type'], 'adminlte_model_has_roles_primary');
            });
        }

        if (! Schema::hasTable('adminlte_model_has_permissions')) {
            Schema::create('adminlte_model_has_permissions', function (Blueprint $table): void {
                $table->unsignedBigInteger('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');

                $table->index(['model_id', 'model_type'], 'adminlte_model_has_permissions_model_id_model_type_index');
                $table->foreign('permission_id')
                    ->references('id')
                    ->on('adminlte_permissions')
                    ->onDelete('cascade');

                $table->primary(['permission_id', 'model_id', 'model_type'], 'adminlte_model_has_permissions_primary');
            });
        }

        if (Schema::hasTable('adminlte_permission_role') && Schema::hasTable('adminlte_role_has_permissions')) {
            $pairs = DB::table('adminlte_permission_role')
                ->select('permission_id', 'role_id')
                ->get();

            foreach ($pairs as $pair) {
                DB::table('adminlte_role_has_permissions')->updateOrInsert(
                    [
                        'permission_id' => $pair->permission_id,
                        'role_id' => $pair->role_id,
                    ],
                    []
                );
            }
        }

        if (Schema::hasTable('adminlte_role_user') && Schema::hasTable('adminlte_model_has_roles')) {
            $assignments = DB::table('adminlte_role_user')
                ->select('role_id', 'user_id')
                ->get();

            foreach ($assignments as $assignment) {
                DB::table('adminlte_model_has_roles')->updateOrInsert(
                    [
                        'role_id' => $assignment->role_id,
                        'model_id' => $assignment->user_id,
                        'model_type' => User::class,
                    ],
                    []
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('adminlte_model_has_permissions');
        Schema::dropIfExists('adminlte_model_has_roles');
        Schema::dropIfExists('adminlte_role_has_permissions');

        if (Schema::hasTable('adminlte_permissions') && Schema::hasColumn('adminlte_permissions', 'guard_name')) {
            Schema::table('adminlte_permissions', function (Blueprint $table): void {
                $table->dropColumn('guard_name');
            });
        }

        if (Schema::hasTable('adminlte_roles') && Schema::hasColumn('adminlte_roles', 'guard_name')) {
            Schema::table('adminlte_roles', function (Blueprint $table): void {
                $table->dropColumn('guard_name');
            });
        }
    }
};
