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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_request_id')->constrained('permission_requests')->onDelete('cascade');
            $table->string('original_name', 255);
            $table->string('stored_name', 255);
            $table->string('file_path', 500);
            $table->string('mime_type', 100);
            $table->bigInteger('file_size');
            $table->string('document_type', 50); // certificado_medico, citacion, etc.
            $table->string('file_hash', 255);
            $table->timestamps();
            
            $table->index(['permission_request_id']);
            $table->index(['document_type']);
            $table->index(['file_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};