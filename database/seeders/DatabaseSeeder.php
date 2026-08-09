<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\PlatformAdmin;
use App\Models\School;
use App\Models\SchoolActivity;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // This app is now multi-tenant: every school is a row in `schools`, and
    // everything else (admins, teachers, students, exams, etc.) belongs to
    // one school via school_id. This seeder creates the FIRST school - your
    // own, Royal Countryside Academy - so the app has something to run
    // against out of the box. Every school that signs up after this one
    // gets created through the (future) signup flow, not through a seeder.
    //
    // Only ONE admin account is created per school by this seeder - fixed
    // credentials below, since there's still no public admin-signup screen:
    //
    //   Username: admin
    //   Password: RCA-Admin#2026
    //
    // CHANGE THIS PASSWORD before deploying to production. Either edit the
    // plain-text value below and re-seed, or log in once and change it via
    // `php artisan tinker` (Admin::first()->update(['password' => 'new-pass']);
    // - the 'hashed' cast on the model will hash it automatically).
    //
    // Other roles still onboard through the app itself, each with a proper
    // ID prefix (ROCAT for teachers, ROCAS for students):
    //   - Teacher: /teacher/signup, needs a single-use invite code generated
    //              by the admin from /admin/invites.
    //   - Student: /enroll (gets a ROCAS id after admin verification)
    public function run(): void
    {
        $school = School::firstOrCreate(
            ['subdomain' => 'royalcountrysideacademy'],
            [
                'name' => 'Royal Countryside Academy',
                'status' => 'active',
                'primary_color' => '#0D3B2E',
                'tagline' => 'Welcome to the RCA Admissions Portal. Complete your enrollment application online and track your admission status through your personalised dashboard.',
                'phone' => '+233 (0) 30 212 3456',
                'contact_email' => 'admissions@royalcountryside.edu.gh',
            ]
        );

        // RCA's own public homepage (resources/views/home.blade.php) stays
        // hand-built and untouched - it doesn't read these fields at all.
        // But the ADMIN/TEACHER/STUDENT dashboard sidebar (layouts/dashboard)
        // reads $currentSchool->logo_path like every other school does, so
        // without this, RCA's own dashboards would be the one place their
        // crest goes missing. Copying it into storage (rather than pointing
        // at public/crest.png directly) keeps every school's logo resolved
        // the exact same way, through the same Storage::disk('public') path
        // used for logos schools upload themselves via Settings.
        if (!$school->logo_path && file_exists(public_path('crest.png'))) {
            $destination = storage_path('app/public/branding');
            if (!is_dir($destination)) {
                mkdir($destination, 0755, true);
            }
            copy(public_path('crest.png'), $destination.'/rca-crest.png');
            $school->update(['logo_path' => 'branding/rca-crest.png']);
        }

        Admin::firstOrCreate(
            ['school_id' => $school->id, 'username' => 'admin'],
            [
                'admin_id' => 'ROCAA000001',
                'email' => 'admin@royalcountryside.edu.gh',
                'full_name' => 'School Administrator',
                'password' => 'RCA-Admin#2026', // hashed automatically via the model's 'hashed' cast
                'role' => 'admin',
            ]
        );

        SchoolActivity::firstOrCreate(
            ['school_id' => $school->id, 'title' => 'Sports Day'],
            [
                'description' => 'Annual inter-house sports competition',
                'activity_date' => '2026-07-14',
                'category' => 'General',
            ]
        );

        SchoolActivity::firstOrCreate(
            ['school_id' => $school->id, 'title' => 'Science Fair'],
            [
                'description' => 'Showcase your science projects',
                'activity_date' => '2026-07-21',
                'category' => 'General',
            ]
        );

        // Platform admin (you) - separate guard/table from any school's admin,
        // sees and manages every school. Fixed credentials for first login:
        //
        //   Email: platform@axispro.example
        //   Password: AxisPro-Platform#2026
        //
        // CHANGE THIS PASSWORD before deploying to production, same as the
        // school admin seed above.
        PlatformAdmin::firstOrCreate(
            ['email' => 'platform@axispro.example'],
            [
                'name' => 'Platform Administrator',
                'password' => 'AxisPro-Platform#2026', // hashed automatically via the model's 'hashed' cast
            ]
        );
    }
}
