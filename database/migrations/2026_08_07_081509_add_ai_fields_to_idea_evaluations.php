<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('idea_evaluations', function (Blueprint $table) {
            $table->string('ai_title')->nullable();
            $table->text('ai_description')->nullable();
            $table->text('ai_general_objective')->nullable();
            $table->json('ai_specific_objectives')->nullable();
            $table->timestamp('ai_enhanced_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('idea_evaluations', function (Blueprint $table) {
            $table->dropColumn(['ai_title', 'ai_description', 'ai_general_objective', 'ai_specific_objectives', 'ai_enhanced_at']);
        });
    }
};
