<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Every other table in the app gets a school_id pointing back to a row
    // here (see the migrations right after this one). A "school" is a
    // tenant/customer of the SaaS product - each one gets its own
    // subdomain, its own admin(s), and never sees another school's data.
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            // Used to resolve which school a request belongs to, e.g.
            // "royalcountryside" -> royalcountryside.yourproduct.com
            $table->string('subdomain', 60)->unique();

            // Branding - applied on top of the shared UI so each school's
            // instance feels like theirs, not a reskin of someone else's.
            $table->string('logo_path')->nullable();
            $table->string('primary_color', 7)->nullable(); // e.g. "#0D3B2E"

            // Billing/lifecycle status. Kept simple for now (Phase 1 is just
            // the data model) - the actual subscription/payment logic comes
            // in a later phase, but the column needs to exist so the tenant
            // resolver can already refuse to serve a suspended school.
            $table->enum('status', ['trial', 'active', 'suspended'])->default('trial');
            $table->timestamp('trial_ends_at')->nullable();

            $table->timestamps();
        });

        // The alter-table migrations that follow this one backfill every
        // pre-existing row (admins, teachers, students, etc.) to
        // school_id = 1, since they all belonged to the single school this
        // app originally ran for. That backfill needs a real row to point
        // to, so it's created here rather than left to the seeder (which
        // runs separately, later, and shouldn't be a hard requirement for
        // `php artisan migrate` alone to leave the database consistent).
        // The seeder's School::firstOrCreate(['subdomain' => ...]) will
        // simply find this same row rather than duplicating it.
        DB::table('schools')->insert([
            'name' => 'Royal Countryside Academy',
            'subdomain' => 'royalcountrysideacademy',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
