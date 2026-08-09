# RCA SMS — Laravel Rebuild

This is the Laravel rebuild of your single-file `index.php` + `ems.sql` app.
It was hand-built in a sandbox without PHP/Composer available to actually run
it, so **you need to do a real first-run on your machine** — treat this as a
strong first draft to debug against, not guaranteed-perfect code.

## 1. Install dependencies

```bash
cd rca-sms-laravel
composer install
```

This reads `composer.json` and downloads Laravel + dependencies into a
`vendor/` folder (not included here — too large to hand-write).

## 2. Environment setup

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set your real MySQL credentials (`DB_USERNAME`, `DB_PASSWORD`).
Create the database first:

```sql
CREATE DATABASE ems;
```

## 3. Run migrations + seed existing data

```bash
php artisan migrate
php artisan db:seed
```

This creates your schema and seeds two sample school activities — **no**
demo admin, teacher, or student accounts are created. Real student/teacher
passwords from your old `ems.sql` were bcrypt hashes tied to the old app;
they were **not** carried over as plaintext (impossible — hashes can't be
reversed), so those specific accounts will need to use the "set password"
flow again.

To create your first login: visit `/admin/login` → "Sign Up". Since no
admin exists yet, this first signup is open with no invite code needed —
every admin created after that requires an invite from an existing admin
(generate one from `/admin/invites`).

## 4. Link the storage folder (for file uploads)

```bash
php artisan storage:link
```

This creates `public/storage` → `storage/app/public`, which is where
`profile_image` and `results_file` uploads now live (replacing the old
`uploads/` folder in the project root).

## 5. Run it

```bash
php artisan serve
```

Visit `http://localhost:8000`.

## What changed structurally

| Old (`index.php`) | New (Laravel) |
|---|---|
| `?page=dashboard` routing | `routes/web.php` named routes |
| Inline `mysqli` queries | Eloquent models in `app/Models` |
| `$_SESSION['admin_id']` etc. | Three auth guards (`admin`, `teacher`, `student`) in `config/auth.php` |
| HTML mixed into PHP echo | Blade views in `resources/views` |
| `CREATE TABLE IF NOT EXISTS` at top of file | Migrations in `database/migrations` |
| Manual file upload validation | Laravel validation rules + `->store()` |
| `uploads/` folder | `storage/app/public` (linked via `storage:link`) |

## July 2026 update — what's new

- **ID system**: every admin, teacher, and student now gets a unique ID on
  creation — `ROCAA######` for admins, `ROCAT######` for teachers,
  `ROCAS######` for students. Login accepts either the ID or the
  username/email you set up with.
- **Invite-gated signup** — the shared teacher signup key (`LimenSpoon`) is
  gone. An admin generates a single-use invite code from `/admin/invites`
  (optionally locked to one email, optionally with an expiry) and hands it
  to the teacher, who enters it during signup at `/teacher/signup`. The
  same system gates new admin signups: the very first admin account can be
  created with no invite (bootstrapping), every admin after that needs an
  invite from an existing admin.
- **Rate limiting** — the admin, teacher, and student login forms (plus
  teacher signup and code verification) are now throttled to block
  brute-force attempts.
- **Teacher self-signup** (`/teacher/signup`) — teachers can now create their
  own account with an admin-issued invite code, instead of admin-only
  account creation. Includes a profile photo upload.
- **Attendance tracking** (`/teacher/attendance`) — a teacher can mark
  present/absent/holiday per student per weekday across a 16-week term (3
  terms/year), plus a cross-term summary view. This is a genuinely large
  grid (~80 date columns) by design, matching the original - expect
  horizontal scrolling on smaller screens.
- **Promote / Repeat** — from the teacher dashboard roster, a teacher can
  promote a student to the next class in the ladder (Creche → ... → JHS 3)
  or mark them as repeating. Only works for students in the teacher's own
  assigned class.

### Correctness note on the attendance schema

The original file's SQL dump (`ems.sql`) looked like it had a 2-column
unique key (`student_id`, `date`), but the *live* PHP code at the top of the
file ran `ALTER TABLE` statements every request that dropped that constraint
and replaced it with a 3-column one: (`student_id`, `date`, `term`). That
means a student can have **one attendance record per date per term** - so
the same calendar date can have up to 3 different records if attendance was
somehow logged against multiple terms. The Laravel migration matches the
3-column version (the actual runtime behavior), not the stale dump.

## Setup for this update

**If this is your first time running the project:** just follow steps 1-5 at
the top of this file as normal - `php artisan migrate --seed` picks up the
new `attendance` table and `teachers.profile_image` column automatically,
no extra steps needed.

**If you already ran the earlier version and have real data in your
database:** don't re-run `migrate:fresh` (it wipes everything). Instead:

```bash
php artisan migrate
```

This only runs the *new* migration files (profile_image column + attendance
table) against your existing database, leaving your current data intact.

## Known gaps / things to verify yourself

- **Email delivery for the teacher OTP code is wired up** (`app/Mail/TeacherVerificationCodeMail.php`,
  sent via `Mail::to($teacher->email)->send(...)`), but it needs real SMTP
  credentials in `.env` (`MAIL_MAILER`, `MAIL_HOST`, `MAIL_USERNAME`,
  `MAIL_PASSWORD`, etc.) to actually deliver anything — with the default
  `MAIL_MAILER=log`, the email just gets written to `storage/logs/laravel.log`
  instead of sent. Sending failures are caught and logged rather than
  breaking login, so double-check that log if a teacher says they never got
  a code. The code is *only* echoed on-screen (`session('dev_code_preview')`)
  when `APP_ENV=local` — never in production.
- **CSS**: `resources/css/app.css` is your original stylesheet copied as-is.
  It hasn't been run through Laravel's asset pipeline (Vite) — for now the
  layout links it directly via `asset('css/app.css')`, which works but
  won't get minification. Ask me if you want Vite wired up properly later.
- I have **not been able to execute this code** (no PHP/Composer in my
  sandbox), so expect to fix small issues on first run — missing imports,
  a typo, etc. Paste me any error and I'll fix it fast.
