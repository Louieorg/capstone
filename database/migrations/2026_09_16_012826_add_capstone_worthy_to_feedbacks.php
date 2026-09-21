<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->boolean('is_capstone_worthy')->default(false)->after('priority_office');
            $table->unsignedBigInteger('capstone_marked_by')->nullable()->after('is_capstone_worthy');
            $table->timestamp('capstone_marked_at')->nullable()->after('capstone_marked_by');
        });
    }

    public function down(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->dropColumn(['is_capstone_worthy', 'capstone_marked_by', 'capstone_marked_at']);
        });
    }
};
