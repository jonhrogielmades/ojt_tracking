# Deploy OJT Tracker on Render Free

This repository includes a Docker image, a Render Blueprint (`render.yaml`), automatic database migrations, one-time administrator bootstrapping, and a zero-cost GitHub Actions workflow for daily student reminders.

## Read the free-plan limits first

Render Free is suitable for a demonstration, capstone, or short-lived pilot—not long-term production records.

- A free web service sleeps after a period without traffic. The first request after sleep has a cold-start delay. Render provides 750 free instance hours per workspace each month.
- A workspace can have one free Render Postgres database. It is limited to 1 GB, expires 30 days after creation, and has a 14-day grace period after expiry. It does not include Render-managed backups.
- The free web-service filesystem is ephemeral. Files written inside the container can disappear on a deploy, restart, or replacement.
- Render does not provide free background workers, persistent disks, Render Cron Jobs, SSH, or one-off jobs. This deployment therefore uses synchronous Laravel queues, automatic startup tasks, and an optional GitHub Actions schedule.

Review the current limits before deploying: [Render Free instances](https://render.com/docs/free), [Render Postgres](https://render.com/docs/postgresql), and [persistent disks](https://render.com/docs/disks).

## What the deployment does automatically

On every container start, `docker/start.sh`:

1. Detects the Render service URL and port.
2. Caches Laravel configuration, routes, and views.
3. Retries and runs `php artisan migrate --force`.
4. Runs `php artisan admin:bootstrap` to create the first Super Administrator only when none exists.
5. Starts Nginx and PHP-FPM under Supervisor.

The Blueprint sets `QUEUE_CONNECTION=sync` because a free background worker is unavailable. Notifications and announcement delivery therefore execute during the initiating request.

## Before deploying

Generate the Laravel encryption key locally. Paste the complete `base64:` value into Render; do not use an arbitrary generated string.

```powershell
php artisan key:generate --show
```

Keep this key stable after deployment. Changing it invalidates encrypted sessions and any application data encrypted with the old key.

Generate a separate random secret for the reminder endpoint:

```powershell
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Prepare these values:

- A long, unique initial administrator password.
- SMTP credentials from a provider that supports port `2525`, or an email provider with an HTTPS API.
- A verified sender email address.
- Optional S3-compatible storage credentials for durable OJT documents and activity-log attachments.

Never commit any of these values or a populated `.env` file.

## Option A: deploy with the Blueprint

1. Push the repository to GitHub with `render.yaml` in the repository root.
2. In the Render Dashboard, select **New → Blueprint**.
3. Connect `jonhrogielmades/ojt_tracking` and select the `main` branch.
4. Review and apply the Blueprint. It creates:
   - `ojt-tracking-rogiel`, a free Docker web service in Singapore.
   - `ojt-tracking-db`, a free PostgreSQL database in Singapore.
5. Render prompts for variables marked `sync: false`. Enter:
   - `APP_KEY`: the complete output of `php artisan key:generate --show`.
   - `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`, and `MAIL_FROM_ADDRESS`.
   - `INITIAL_ADMIN_NAME`, `INITIAL_ADMIN_EMAIL`, and `INITIAL_ADMIN_PASSWORD`.
   - `RENDER_CRON_SECRET`: the separate 64-character secret generated above.
6. Deploy and wait for `/up` to become healthy.
7. Sign in at `https://<service-name>.onrender.com/admin/login`.
8. Immediately remove `INITIAL_ADMIN_PASSWORD` from the Render environment. You can also remove the initial name and email. Later restarts skip bootstrapping because an administrator already exists.

The Blueprint injects the database's **internal connection string** into `DB_URL`. Do not replace it with the external URL inside Render.

## Option B: create the services manually

### 1. Create PostgreSQL

1. Select **New → PostgreSQL** in Render.
2. Choose the same region as the web service, preferably Singapore.
3. Select the free plan and create the database.
4. From the database's **Connect** menu, copy the **Internal Database URL** for the web service.

Render documents internal and external connections in [Creating and connecting to Render Postgres](https://render.com/docs/postgresql-creating-connecting).

### 2. Create the web service

1. Select **New → Web Service** and connect the GitHub repository.
2. Choose the `main` branch and **Docker** runtime.
3. Select the free instance type.
4. Keep the repository root as the Docker context and use `./Dockerfile`.
5. Set the health-check path to `/up`.
6. Do not override the Docker start command.
7. Add the environment variables below, then deploy.

## Render environment variables

The repository includes `.env.render.example` as a copyable reference. In Render, add variables through the dashboard or Blueprint—do not upload a plaintext `.env` file.

```dotenv
APP_NAME="OJT Tracker"
APP_ENV=production
APP_KEY=base64:PASTE_THE_KEY_GENERATED_BY_ARTISAN
APP_DEBUG=false
APP_URL=https://YOUR-SERVICE.onrender.com
ASSET_URL=https://YOUR-SERVICE.onrender.com
APP_FORCE_HTTPS=true
TRUSTED_PROXIES=*
APP_TIMEZONE=Asia/Manila

LOG_CHANNEL=stderr
LOG_LEVEL=info

DB_CONNECTION=pgsql
DB_URL=PASTE_THE_RENDER_INTERNAL_DATABASE_URL
DB_SSLMODE=prefer

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=sync
BROADCAST_CONNECTION=log

FILESYSTEM_DISK=local
DOCUMENTS_DISK=local
ATTACHMENTS_DISK=local
DATABASE_BACKUP_ENABLED=false

MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=YOUR_SMTP_HOST
MAIL_PORT=2525
MAIL_USERNAME=YOUR_SMTP_USERNAME
MAIL_PASSWORD=YOUR_SMTP_PASSWORD
MAIL_FROM_ADDRESS=YOUR_VERIFIED_SENDER
MAIL_FROM_NAME="OJT Tracker"

INITIAL_ADMIN_NAME="OJT Administrator"
INITIAL_ADMIN_EMAIL=YOUR_ADMIN_EMAIL
INITIAL_ADMIN_PASSWORD=USE_A_LONG_UNIQUE_PASSWORD

RENDER_CRON_SECRET=USE_A_DIFFERENT_64_CHARACTER_RANDOM_SECRET
```

If the start script receives `RENDER_EXTERNAL_URL`, it automatically supplies `APP_URL` and `ASSET_URL`. Setting them explicitly is still recommended for mail links and clarity.

### Email on Render Free

Do not use `MAIL_MAILER=log` in production: password-reset links and MFA codes would be written to service logs. Render Free restricts common SMTP ports, so use a provider that supports port `2525`. If your provider only offers an HTTPS email API, configure its supported Laravel transport instead of trying ports `25`, `465`, or `587`.

## Durable private file storage with S3 or Cloudflare R2

With `DOCUMENTS_DISK=local` and `ATTACHMENTS_DISK=local`, uploaded OJT documents and activity-log attachments are private but ephemeral. Database rows survive; the corresponding files might not.

For durable OJT documents and activity-log attachments, point both disks at the same private S3-compatible bucket:

```dotenv
DOCUMENTS_DISK=s3
ATTACHMENTS_DISK=s3
AWS_ACCESS_KEY_ID=YOUR_ACCESS_KEY
AWS_SECRET_ACCESS_KEY=YOUR_SECRET_KEY
AWS_DEFAULT_REGION=YOUR_REGION
AWS_BUCKET=YOUR_PRIVATE_BUCKET
AWS_ENDPOINT=YOUR_S3_COMPATIBLE_ENDPOINT
AWS_USE_PATH_STYLE_ENDPOINT=false
AWS_URL=
```

For Cloudflare R2, use `AWS_DEFAULT_REGION=auto` and the endpoint shown in the R2 S3 API settings. Keep the bucket private; downloads continue through authenticated application controllers. `AWS_ENDPOINT` can be omitted when using AWS S3 itself.

## Automatic migrations and first administrator

No manual database command is required for a new deployment. The start script runs all migrations before starting the web server and then invokes the idempotent administrator bootstrap command.

The bootstrap command:

- Creates a Super Administrator only when no administrator exists.
- Validates the name, email, and password.
- Never replaces or resets an existing administrator.
- Does nothing when all three initial administrator variables are absent.
- Reports invalid or incomplete bootstrap values and skips administrator creation without taking the web service offline. Correct all three values and redeploy to create the first administrator.

After the first successful login, delete `INITIAL_ADMIN_PASSWORD` from Render and redeploy. Keeping a reusable plaintext bootstrap password in environment settings is unnecessary risk.

## Free PostgreSQL lifecycle, backup, and restore

The application's built-in database-backup feature is disabled on this deployment because it does not create PostgreSQL dumps and local backup files would be ephemeral. Render Free also provides no managed database backups.

Before the free database expires, use the **External Database URL** from Render's database **Connect** menu and a local PostgreSQL client. The external URL is a secret.

The Blueprint blocks public database access by default. Temporarily add only your current public IP to the database's access-control list before using the external URL, then remove that rule after the backup or restore finishes.

### Create a portable dump

PowerShell:

```powershell
$env:RENDER_EXTERNAL_DATABASE_URL = 'postgresql://USER:PASSWORD@HOST/DATABASE'
pg_dump --dbname="$env:RENDER_EXTERNAL_DATABASE_URL" --format=custom --no-owner --no-acl --file="ojt-tracking.dump"
```

Bash:

```bash
export RENDER_EXTERNAL_DATABASE_URL='postgresql://USER:PASSWORD@HOST/DATABASE'
pg_dump --dbname="$RENDER_EXTERNAL_DATABASE_URL" --format=custom --no-owner --no-acl --file=ojt-tracking.dump
```

Store the dump outside the repository in protected storage.

### Restore into a replacement database

Create a new, empty PostgreSQL database, copy its **External Database URL**, and restore before connecting the web service to it:

PowerShell:

```powershell
$env:NEW_RENDER_EXTERNAL_DATABASE_URL = 'postgresql://USER:PASSWORD@NEW_HOST/NEW_DATABASE'
pg_restore --dbname="$env:NEW_RENDER_EXTERNAL_DATABASE_URL" --no-owner --no-acl --exit-on-error "ojt-tracking.dump"
```

Bash:

```bash
export NEW_RENDER_EXTERNAL_DATABASE_URL='postgresql://USER:PASSWORD@NEW_HOST/NEW_DATABASE'
pg_restore --dbname="$NEW_RENDER_EXTERNAL_DATABASE_URL" --no-owner --no-acl --exit-on-error ojt-tracking.dump
```

Do not restore a full dump over an already migrated database. Restore into an empty replacement database, update the web service's `DB_URL` to that database's **Internal Database URL**, and redeploy. Use a `pg_dump` client version equal to or newer than the database server version.

## Zero-cost daily reminders with GitHub Actions

Render Cron Jobs and background workers are not available on the free plan. `.github/workflows/render-reminders.yml` calls a protected application endpoint every day at **10:00 UTC / 6:00 PM Asia/Manila**.

In the GitHub repository:

1. Open **Settings → Secrets and variables → Actions**.
2. Add repository secret `RENDER_APP_URL` with the service base URL, such as `https://ojt-tracking-rogiel.onrender.com`.
3. Add repository secret `RENDER_CRON_SECRET` with exactly the same value configured in Render.
4. Open **Actions → Render daily student reminders → Run workflow** to test it.

The endpoint deliberately returns `404` when its secret is absent or incorrect. The workflow uses retries and long timeouts so the first request can wake a sleeping service. GitHub scheduled workflows run from the default branch, are best-effort, and can be delayed during busy periods; see [GitHub's scheduled workflow documentation](https://docs.github.com/actions/using-workflows/events-that-trigger-workflows#schedule).

For public repositories, GitHub automatically disables scheduled workflows after 60 days without repository activity. If reminders stop, open the repository's **Actions** tab, select **Render daily student reminders**, choose **Enable workflow**, and run it manually once. GitHub documents this behavior in [Disabling and enabling a workflow](https://docs.github.com/actions/how-tos/manage-workflow-runs/disable-and-enable-workflows).

## Post-deployment checklist

- Open `/up` and confirm a successful health response.
- Sign in through `/admin/login` with the bootstrap administrator.
- Remove `INITIAL_ADMIN_PASSWORD` from Render.
- Run the GitHub reminder workflow manually and confirm a successful JSON response in its logs.
- Configure real SMTP before testing password recovery or administrator MFA.
- Create a test student and verify Student ID login.
- If private file retention matters, configure both `DOCUMENTS_DISK=s3` and `ATTACHMENTS_DISK=s3` before accepting uploads.
- Schedule a local `pg_dump` reminder well before the 30-day database expiry.
- Watch Render logs for migration, mail, or database connection errors after every deploy.
