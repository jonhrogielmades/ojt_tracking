# OJT Tracker

OJT Tracker is a Laravel Progressive Web App for student daily time records, activity logs, training-hour progress, private OJT documents, and print-ready reports. Students and administrators use separate, role-protected workspaces; there are no supervisor or company accounts.

## Requirements

- PHP 8.3 or newer with the extensions Laravel requires
- Composer
- MySQL 8 or compatible MariaDB/MySQL server
- Node.js 18 or newer and npm
- A current Chrome, Edge, or Safari browser for PWA installation

This project was built with Laravel 13, Tailwind CSS 4, and the PHP 8.3/MySQL 8 toolchain included with Laragon.

## Laragon setup on Windows

Open **Laragon Terminal** so `php`, `composer`, `mysql`, `node`, and `npm` use Laragon's configured versions. Then change to the project directory:

```powershell
cd C:\laragon\www\ojt_tracking
```

Create the MySQL database in HeidiSQL, phpMyAdmin, or the MySQL command line. The equivalent SQL is:

```sql
CREATE DATABASE ojt_tracking
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
```

Install the application and frontend dependencies:

```powershell
composer install
npm install
Copy-Item .env.example .env
php artisan key:generate
```

The supplied `.env.example` already contains the usual Laragon defaults:

```dotenv
APP_NAME="OJT Tracker"
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Asia/Manila

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ojt_tracking
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database
MAIL_MAILER=log
```

Change the database username or password in `.env` if your Laragon installation uses different credentials. `MAIL_MAILER=log` is suitable only for local testing; configure SMTP or another real mail transport before using password recovery, password-setup links, or administrator email MFA. Never commit the populated `.env` file.

Create the tables and build the production assets:

```powershell
php artisan migrate
npm run build
```

Create the first administrator through the interactive console command. The password is requested securely and is not accepted as a command-line option:

```powershell
php artisan admin:create
```

Start the app on a local origin that supports service workers:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Run the queue worker and scheduler in two additional Laragon Terminal windows. Queued password emails, announcements, review feedback, reminders, and database notifications will wait until the worker is running.

```powershell
php artisan queue:work --tries=3
php artisan schedule:work
```

In production, keep `queue:work` under a process monitor and run `php artisan schedule:run` every minute. The scheduler sends student reminders at 6:00 PM and creates/prunes a database backup at 2:00 AM in the application timezone.

Open `http://127.0.0.1:8000`, register a student account, and complete the placement profile. Student IDs must use seven digits followed by `-1` or `-2`, such as `2310060-1`. Administrators sign in separately at `http://127.0.0.1:8000/admin/login` using their email and password.

Browsers treat localhost/127.0.0.1 as a secure development origin. If you use Laragon's `http://ojt_tracking.test` virtual host instead, normal web features work but PWA installation may be unavailable over plain HTTP; enable Laragon HTTPS and set `APP_URL=https://ojt_tracking.test` for full PWA behavior.

## Roles and account access

- **Students** sign in at `/login` with their Student ID and password. They can access only their own dashboard, DTR, activity logs, documents, profile, reports, and offline drafts.
- **Super Administrators** have all operational and governance capabilities, including administrator accounts, audit logs, backups, and permanent purge of already-archived students.
- **OJT Coordinators** manage students, terms, companies, approvals, documents, reports, announcements, imports, risk monitoring, and system health, but cannot manage administrators, backups, or the audit trail.
- **Auditors / Viewers** have read-only access to students, reports/exports, risk indicators, system health, and the audit trail.
- **Document Officers** can view student identity details and manage document requirements and reviews only.
- All administrator roles sign in separately at `/admin/login` with email and password. Each administrator can enable a hashed, expiring email OTP from **Sign-in security**.
- Public registration always creates a student account. It cannot grant administrator access.
- Deactivating a student prevents sign-in without deleting their existing OJT records.
- `php artisan admin:create` creates the first Super Administrator. A Super Administrator creates additional scoped administrator accounts in the UI; passwords are never shown or stored in plain text.
- Student deletion is recoverable archive-by-default. Restore preserves records and private files; only a Super Administrator can permanently purge an archived account.

## Operations, mail, and backups

- Student CSV import uses a validate/preview/confirm flow. Required headers are `name,email,student_id,program,section`; imported students receive queued password-setup links. Student and progress CSV exports escape spreadsheet formulas.
- Configure academic terms, programs, sections, holidays, partner companies, document requirements, report branding, and announcements from the admin workspace.
- The approval queue reviews weekly submissions, correction requests, and private documents. Decisions are audited and delivered to the student's notification inbox.
- The notification center aggregates approval work, document reviews, failed jobs, backup health, and notifications visible to the current administrator role.
- Backups are private and tracked by checksum. For MySQL, ensure `mysqldump` is in `PATH` or set `MYSQLDUMP_BINARY` in `.env`. Retention defaults to 30 days and can be changed with `DATABASE_BACKUP_RETENTION_DAYS`.

Useful backup commands:

```powershell
php artisan backup:create --prune
php artisan backup:verify 1
php artisan backup:prune
```

## Development

For live frontend changes, run these in separate Laragon Terminal windows:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
npm run dev
```

Run the automated checks with:

```powershell
php artisan test
vendor\bin\pint --format agent
npm run build
```

Private document and activity attachments use Laravel's local private disk under `storage/app/private`. Do not run `php artisan storage:link` for these files; authenticated controllers perform all downloads after ownership checks.

## Installing the PWA

Sign in, then open **Installation & offline help** from the app navigation.

- **Chrome or Edge on desktop:** select the install icon in the address bar, or use the browser menu and choose **Install OJT Tracker** / **Apps → Install this site as an app**.
- **Android Chrome:** open the browser menu and choose **Install app** or **Add to Home screen**.
- **iPhone or iPad Safari:** tap **Share**, choose **Add to Home Screen**, and confirm.

The manifest includes standard and maskable 192×192 and 512×512 icons. The service worker caches only the public offline page, manifest, icons, and compiled static assets. It deliberately does not cache authenticated pages, private responses, uploaded files, passwords, or synced student records.

## Offline drafts and sync

- New DTR and text-only activity forms can be saved while offline.
- Only unsynced drafts are kept in IndexedDB, namespaced by a random key belonging to the signed-in student.
- Each draft has a UUID idempotency key. Retrying the same payload cannot create a duplicate server record.
- The app retries when it opens, when connectivity returns, or when the student selects **Sync now**. Background Sync is used when the browser supports it; the normal retry path remains available.
- Official DTR hours are always recalculated and validated on the server.
- Validation failures and date/idempotency conflicts remain on the device with a **Needs review** status. A conflicting saved DTR can be opened for editing, or the local draft can be discarded explicitly.
- Activity attachments and OJT documents require an internet connection and are never stored in the offline queue.
- Logging out with pending drafts opens a warning with **Sync, then log out**, **Delete drafts and log out**, and **Cancel**. Confirmed logout clears that student's remaining local drafts so another user of the device cannot see them.

## Main features

- Strict Student ID format (`2310060-1` or `2310060-2` style) with Student ID and password login
- Student and administrator password recovery with queued, time-limited reset links
- Separate administrator sign-in, optional email OTP MFA, and four capability-scoped admin roles
- Student account management with search, progress review, activation/deactivation, bulk import/export, archive/restore, and Super Administrator purge
- Student registration, logout, and placement profile
- Required, completed, and remaining OJT-hour dashboard with weekly targets and completion forecast
- Server-calculated DTR entries with placement-date/break validation, one entry per date, totals, edit history, and submitted-week locking
- Searchable activity logs with optional private attachments
- Draft/submitted/approved/change-requested weekly accomplishment workflow and student correction requests
- Calendar, skills portfolio, announcements, reminders, feedback/notification inbox, and printable summaries
- Requirement-driven private document uploads, authenticated downloads, review decisions, and feedback
- Monthly DTR and weekly accomplishment reports with configurable institutional branding, print/save-to-PDF layouts, and optional blank signature lines
- Admin approval/document queues, at-risk dashboard, notification center, report templates, audit trail, system health, and verified backup lifecycle
- CSV imports with preview and exports protected against spreadsheet formula injection
- Larger-text/high-contrast preferences, reduced-motion support, keyboard focus, skip links, and touch-friendly controls
- Installable responsive PWA with online/offline state, IndexedDB draft queue, idempotent sync, and explicit conflict handling
