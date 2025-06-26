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
        Schema::create('biometric_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('permission_request_id')->nullable()->constrained('permission_requests')->onDelete('set null');
            $table->enum('record_type', ['entry', 'exit', 'permission_start', 'permission_end']);
            $table->timestamp('timestamp');
            $table->string('biometric_data_hash', 255);
            $table->string('device_id', 50);
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('is_valid')->default(true);
            $table->timestamps();
            
            $table->index(['user_id']);
            $table->index(['timestamp']);
            $table->index(['record_type']);
            $table->index(['device_id']);
            $table->index(['is_valid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biometric_records');
    }
};