<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            // A short welcome line shown on the school's own homepage, e.g.
            // "Complete your enrollment application online...". Nullable -
            // the homepage falls back to a generic line if not set.
            $table->text('tagline')->nullable()->after('primary_color');
            $table->string('phone', 30)->nullable()->after('tagline');
            $table->string('contact_email', 150)->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['tagline', 'phone', 'contact_email']);
        });
    }
};
