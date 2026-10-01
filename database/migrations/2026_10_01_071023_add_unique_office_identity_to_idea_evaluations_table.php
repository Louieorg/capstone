<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $conflicts = DB::table('idea_evaluations')
            ->select('office_id', 'category', 'idea_title')
            ->selectRaw('COUNT(*) AS duplicate_count')
            ->whereNotNull('office_id')
            ->groupBy('office_id', 'category', 'idea_title')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($conflicts->isNotEmpty()) {
            throw new \RuntimeException('Resolve duplicate office-backed idea evaluation identities before adding the unique index: '.$conflicts->toJson());
        }

        Schema::table('idea_evaluations', function (Blueprint $table) {
            $table->unique(['office_id', 'category', 'idea_title'], 'idea_evaluations_office_identity_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('idea_evaluations', function (Blueprint $table) {
            $table->dropUnique('idea_evaluations_office_identity_unique');
        });
    }
};
