<?php

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
        Schema::table('users', function (Blueprint $table) {
            // Agregar nuevas columnas
            $table->string('dni', 8)->unique()->after('id');
            $table->string('first_name', 100)->after('dni');
            $table->string('last_name', 100)->after('first_name');
            $table->foreignId('department_id')->nullable()->after('password')->constrained('departments')->onDelete('set null');
            $table->foreignId('role_id')->after('department_id')->constrained('roles')->onDelete('cascade');
            $table->foreignId('immediate_supervisor_id')->nullable()->after('role_id')->constrained('users')->onDelete('set null');
            $table->boolean('is_active')->default(true)->after('immediate_supervisor_id');
            $table->timestamp('two_factor_expires_at')->nullable()->after('is_active');
            $table->string('two_factor_secret')->nullable()->after('two_factor_expires_at');
            
            // Modificar columnas existentes
            $table->string('email', 150)->change();
            
            // Eliminar columna name si existe
            $table->dropColumn('name');
            
            // Agregar índices
            $table->index(['dni']);
            $table->index(['email']);
            $table->index(['is_active']);
            $table->index(['department_id']);
            $table->index(['immediate_supervisor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Eliminar índices
            $table->dropIndex(['dni']);
            $table->dropIndex(['email']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['department_id']);
            $table->dropIndex(['immediate_supervisor_id']);
            
            // Eliminar foreign keys
            $table->dropForeign(['department_id']);
            $table->dropForeign(['role_id']);
            $table->dropForeign(['immediate_supervisor_id']);
            
            // Eliminar columnas agregadas
            $table->dropColumn([
                'dni',
                'first_name', 
                'last_name',
                'department_id',
                'role_id',
                'immediate_supervisor_id',
                'is_active',
                'two_factor_expires_at',
                'two_factor_secret'
            ]);
            
            // Restaurar columna name
            $table->string('name')->after('id');
            
            // Restaurar email original
            $table->string('email')->change();
        });
    }
};