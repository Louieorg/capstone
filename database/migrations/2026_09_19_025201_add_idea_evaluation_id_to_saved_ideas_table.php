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
        Schema::table('saved_ideas', function (Blueprint $table) {
            $table->foreignId('idea_evaluation_id')
                ->nullable()
                ->after('category')
                ->constrained('idea_evaluations')
                ->nullOnDelete();

            $table->unique(['user_id', 'idea_evaluation_id'], 'saved_ideas_user_evaluation_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saved_ideas', function (Blueprint $table) {
            $table->dropUnique('saved_ideas_user_evaluation_unique');
            $table->dropConstrainedForeignId('idea_evaluation_id');
        });
    }
};
