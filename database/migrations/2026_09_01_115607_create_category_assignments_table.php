<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('category')->unique();
            $table->string('office'); // 'office_academic' | 'office_chief'
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_assignments');
    }
};
