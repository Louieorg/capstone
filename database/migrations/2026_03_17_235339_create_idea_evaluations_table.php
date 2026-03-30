<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idea_evaluations', function (Blueprint $table) {
            $table->id();

            $table->string('idea_title');
            $table->string('category');

            $table->integer('feasibility')->default(0);
            $table->integer('impact')->default(0);
            $table->integer('complexity')->default(0);
            $table->integer('innovation')->default(0);

            $table->float('overall_score')->default(0);
            $table->string('recommendation')->nullable();

            $table->unsignedBigInteger('adviser_id')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idea_evaluations');
    }
};