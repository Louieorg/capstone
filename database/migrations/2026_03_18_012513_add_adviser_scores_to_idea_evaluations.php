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

    $table->integer('adviser_feasibility')->nullable();
    $table->integer('adviser_impact')->nullable();
    $table->integer('adviser_complexity')->nullable();
    $table->integer('adviser_innovation')->nullable();

    $table->float('final_score')->nullable();

});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('idea_evaluations', function (Blueprint $table) {
            //
        });
    }
};
