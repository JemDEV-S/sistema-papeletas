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
        Schema::create('permission_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 20)->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('permission_type_id')->constrained('permission_types')->onDelete('cascade');
            $table->datetime('start_datetime');
            $table->datetime('end_datetime');
            $table->decimal('requested_hours', 5, 2);
            $table->text('reason');
            $table->enum('status', [
                'draft',
                'pending_supervisor',
                'pending_hr',
                'approved',
                'rejected',
                'in_execution',
                'completed',
                'cancelled'
            ])->default('draft');
            $table->json('metadata')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->integer('current_approval_level')->default(0);
            $table->timestamps();
            
            $table->index(['request_number']);
            $table->index(['user_id']);
            $table->index(['status']);
            $table->index(['submitted_at']);
            $table->index(['start_datetime']);
            $table->index(['end_datetime']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_requests');
    }
};