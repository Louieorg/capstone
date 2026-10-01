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
        Schema::create('office_confirmation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('idea_evaluation_id')->constrained('idea_evaluations')->restrictOnDelete();
            $table->foreignId('office_id')->constrained('offices')->restrictOnDelete();
            $table->string('status')->default('pending');
            $table->string('decision_outcome')->nullable();
            $table->foreignId('decided_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('decision_at')->nullable();
            $table->string('office_cluster_key');
            $table->json('source_feedback_ids');
            $table->char('provenance_fingerprint', 64);
            $table->timestamp('stale_at')->nullable();
            $table->foreignId('active_opportunity_id')->nullable()->constrained('idea_evaluations')->restrictOnDelete();
            $table->unique('active_opportunity_id', 'office_confirmation_active_opportunity_unique');
            $table->index(['office_id', 'status'], 'office_confirmation_office_status_index');
            $table->index(['requester_user_id', 'idea_evaluation_id'], 'office_confirmation_requester_opportunity_index');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('office_confirmation_requests');
    }
};
