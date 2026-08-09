<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->cascadeOnDelete();
        });

        // Every admin that existed before multi-tenancy belongs to school #1
        // (the original single-school setup, seeded as the first School row).
        DB::table('admins')->whereNull('school_id')->update(['school_id' => 1]);

        // These were globally unique (only one "admin" username could ever
        // exist across the whole product). Now that many schools share this
        // table, each school needs its own "admin" - so the constraint moves
        // to (school_id, column) instead of just (column).
        Schema::table('admins', function (Blueprint $table) {
            $table->dropUnique('admins_admin_id_unique');
            $table->dropUnique('admins_username_unique');
            $table->dropUnique('admins_email_unique');
            $table->unique(['school_id', 'admin_id']);
            $table->unique(['school_id', 'username']);
            $table->unique(['school_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropUnique(['school_id', 'admin_id']);
            $table->dropUnique(['school_id', 'username']);
            $table->dropUnique(['school_id', 'email']);
            $table->string('admin_id', 20)->unique()->change();
            $table->string('username', 50)->unique()->change();
            $table->string('email', 100)->unique()->change();
            $table->dropConstrainedForeignId('school_id');
        });
    }
};
