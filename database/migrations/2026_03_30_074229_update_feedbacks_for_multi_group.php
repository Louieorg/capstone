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
       Schema::table('feedback', function (Blueprint $table) {
    // Store multiple groups as JSON instead of a plain string
    $table->json('affected_group')->nullable()->change();
 
    // Store the raw "other" category text separately for admin review
    $table->string('category_other')->nullable()->after('category');
    $table->string('current_process_other')->nullable()->after('current_process');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
