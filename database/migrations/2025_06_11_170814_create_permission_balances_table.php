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
        Schema::create('permission_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('permission_type_id')->constrained('permission_types')->onDelete('cascade');
            $table->year('year');
            $table->tinyInteger('month'); // 1-12
            $table->decimal('available_hours', 6, 2);
            $table->decimal('used_hours', 6, 2)->default(0);
            $table->decimal('remaining_hours', 6, 2);
            $table->timestamps();
            
            $table->index(['user_id', 'year', 'month']);
            $table->index(['permission_type_id']);
            $table->unique(['user_id', 'permission_type_id', 'year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_balances');
    }
};