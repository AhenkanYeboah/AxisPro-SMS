<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_activities', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('activity_date')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->string('category', 50)->default('General');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_activities');
    }
};
