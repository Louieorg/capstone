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
        Schema::table('idea_evaluations', function (Blueprint $table) {
            $table->string('office_cluster_key')->nullable()->after('office_id');
            $table->json('office_source_feedback_ids')->nullable()->after('office_cluster_key');
            $table->char('office_provenance_fingerprint', 64)->nullable()->after('office_source_feedback_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('idea_evaluations', function (Blueprint $table) {
            $table->dropColumn([
                'office_cluster_key',
                'office_source_feedback_ids',
                'office_provenance_fingerprint',
            ]);
        });
    }
};
