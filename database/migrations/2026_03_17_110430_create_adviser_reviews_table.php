<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('adviser_reviews', function (Blueprint $table) {
        $table->id();

        // What idea is being reviewed
        $table->string('idea_title');
        $table->string('category');

        // Adviser feedback
        $table->text('comment')->nullable();

        // Adviser decision
        $table->enum('recommendation', [
            'Recommended',
            'Needs Revision',
            'Not Recommended'
        ]);

        // Who reviewed it (optional but good)
        $table->unsignedBigInteger('user_id')->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adviser_reviews');
    }
};
