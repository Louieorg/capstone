<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->text('title_en')->nullable()->after('title');
            $table->text('description_en')->nullable()->after('description');
            $table->text('impact_en')->nullable()->after('impact');
            $table->timestamp('translated_at')->nullable()->after('impact_en');
        });
    }

    public function down(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropColumn(['title_en', 'description_en', 'impact_en', 'translated_at']);
        });
    }
};
