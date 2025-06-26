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
        Schema::create('digital_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_request_id')->constrained('permission_requests')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('signature_type', ['request', 'approval_level_1', 'approval_level_2']);
            $table->string('certificate_serial', 255);
            $table->string('signature_hash', 255);
            $table->timestamp('signed_at');
            $table->json('certificate_data')->nullable();
            $table->boolean('is_valid')->default(true);
            $table->timestamps();
            
            $table->index(['permission_request_id']);
            $table->index(['user_id']);
            $table->index(['signature_type']);
            $table->index(['signed_at']);
            $table->index(['is_valid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digital_signatures');
    }
};