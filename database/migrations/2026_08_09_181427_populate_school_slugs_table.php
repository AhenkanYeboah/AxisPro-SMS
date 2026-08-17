<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;
use App\Models\School;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        School::whereNull('slug')->orWhere('slug', '')->get()->each(function ($school) {
            $school->update([
                'slug' => Str::slug($school->name),
            ]);
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
