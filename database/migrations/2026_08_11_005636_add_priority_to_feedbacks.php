<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->boolean('is_priority')->default(false)->after('is_flagged');
            $table->string('priority_status')->nullable()->after('is_priority'); // pending | taken | resolved
            $table->unsignedBigInteger('priority_taken_by')->nullable()->after('priority_status');
            $table->timestamp('priority_taken_at')->nullable()->after('priority_taken_by');
            $table->timestamp('priority_resolved_at')->nullable()->after('priority_taken_at');
        });
    }

    public function down(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->dropColumn(['is_priority', 'priority_status', 'priority_taken_by', 'priority_taken_at', 'priority_resolved_at']);
        });
    }
};
