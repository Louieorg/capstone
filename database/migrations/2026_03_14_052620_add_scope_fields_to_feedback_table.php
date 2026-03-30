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
        $table->string('frequency')->after('impact');
        $table->string('current_process')->after('frequency');
        $table->string('affected_users')->after('current_process');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            //
        });
    }
};
