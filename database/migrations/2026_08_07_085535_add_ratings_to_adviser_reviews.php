<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adviser_reviews', function (Blueprint $table) {
            $table->unsignedTinyInteger('feasibility')->nullable()->after('recommendation');
            $table->unsignedTinyInteger('impact')->nullable()->after('feasibility');
            $table->unsignedTinyInteger('complexity')->nullable()->after('impact');
            $table->unsignedTinyInteger('innovation')->nullable()->after('complexity');
        });
    }

    public function down(): void
    {
        Schema::table('adviser_reviews', function (Blueprint $table) {
            $table->dropColumn(['feasibility', 'impact', 'complexity', 'innovation']);
        });
    }
};
