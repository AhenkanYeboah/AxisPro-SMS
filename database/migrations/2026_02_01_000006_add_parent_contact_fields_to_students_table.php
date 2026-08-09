<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Students at RCA range from Creche through JHS 3 - many are far too
        // young to have their own phone/email. The existing `phone`/`email`
        // columns on Student are ambiguous about whose contact they actually
        // are in practice. Rather than guess, add explicit parent/guardian
        // fields: fee reminders and notices should go here, falling back to
        // the student's own phone/email only if these are blank (older JHS
        // students who may genuinely have their own).
        Schema::table('students', function (Blueprint $table) {
            $table->string('parent_name', 150)->nullable()->after('next_of_kin');
            $table->string('parent_phone', 20)->nullable()->after('parent_name');
            $table->string('parent_email', 150)->nullable()->after('parent_phone');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['parent_name', 'parent_phone', 'parent_email']);
        });
    }
};
