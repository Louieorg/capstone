<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One row per generated cluster explanation, addressed by the content hash
     * of the evidence package that produced it. Every AI-owned column is
     * nullable so a row can exist as a record of an attempt without holding
     * model output. This table holds no DSS output, no score and no user
     * reference, and adds no foreign key to any existing table.
     */
    public function up(): void
    {
        Schema::create('cluster_explanations', function (Blueprint $table) {
            $table->id();

            $table->string('evidence_hash', 64)->unique();
            $table->string('category')->index();
            $table->string('cluster_label')->nullable();

            $table->string('language', 5)->default('en');
            $table->string('prompt_version', 16)->default('1');
            $table->string('model', 64)->nullable();

            $table->text('summary')->nullable();
            $table->json('patterns')->nullable();
            $table->json('experiences')->nullable();

            $table->string('status', 16)->default('complete');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('last_attempted_at')->nullable();
            $table->timestamp('generated_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops only the table this migration created.
     */
    public function down(): void
    {
        Schema::dropIfExists('cluster_explanations');
    }
};
