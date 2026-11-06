# Deploying the LMS to Render with CI/CD (GitHub Actions + Render)

Flow: **push to `main` → GitHub Actions runs tests + builds the Docker image → if green, it calls Render's deploy hook → Render builds and releases → the container runs migrations on start.**

Render has no PHP runtime, so the app runs from the `Dockerfile` in this repo (PHP 8.4 + Apache).

## 0. What you need
PHP 8.3+, Composer, Git, a GitHub account, a Render account (sign in with GitHub).

## 1. Update your local project and test it
```bash
unzip -o lms-update.zip -d lms         # Windows: Expand-Archive -Force lms-update.zip -DestinationPath lms
cd lms
git rm -r --cached scripts 2>/dev/null; rm -rf scripts       # old Render script (replaced by docker/start.sh)
rm -f tests/Feature/ExampleTest.php                          # replaced by SmokeTest / ApprovalFlowTest
php artisan migrate                                          # new tables: notifications, approval flow, ...
php artisan optimize:clear
php artisan test                                             # must be green before you push
```

## 2. Generate the production secrets
```bash
php artisan key:generate --show      # copy the whole "base64:..." value -> APP_KEY
```
Pick a strong `ADMIN_PASSWORD` (this becomes the first administrator on the production site).

## 3. Put the code on GitHub
```bash
git add -A
git commit -m "Approval flows, notifications, pages, Render CI/CD"
# create an EMPTY repo on github.com (no README), then:
git remote add origin https://github.com/<you>/<repo>.git      # skip if a remote already exists (git remote -v)
git branch -M main
git push -u origin main
```
Never commit `.env` (it is git-ignored). `composer.lock` MUST be committed.

## 4. Create the services on Render (Blueprint)
1. Render dashboard → **New → Blueprint** → connect the GitHub repo → it reads `render.yaml`.
2. It proposes a Postgres database `lms-db` and a web service `lms`. Fill the prompted secrets:
   | Variable | Value |
   |---|---|
   | `APP_KEY` | the `base64:...` value from step 2 |
   | `APP_URL` | `https://<service-name>.onrender.com` (you can edit it after the first deploy) |
   | `ADMIN_EMAIL` | your admin login e-mail |
   | `ADMIN_PASSWORD` | the strong password from step 2 |
3. **Apply**. The first build takes several minutes. On start, the container runs `php artisan migrate --force` and (because `RUN_SEED=true`) seeds departments/categories and creates your admin.
4. When it is live, open the URL and sign in. Then in Render → *Environment* set **`RUN_SEED` = `false`** and save (so later deploys do not seed again).

## 5. Wire up CI/CD
1. Render → service `lms` → **Settings → Deploy Hook** → copy the URL.
2. GitHub repo → **Settings → Secrets and variables → Actions → New repository secret**
   name `RENDER_DEPLOY_HOOK_URL`, value = that URL.
3. Render → service → **Settings → Build & Deploy → Auto-Deploy = Off** (GitHub Actions triggers deploys, so a failing test can never ship).
4. Push any change to `main` and watch **GitHub → Actions**: `Tests` → `Docker image builds` → `Deploy to Render`. Pull requests run the tests only.

## 6. Day-to-day
* Deploy = `git push origin main`. Roll back = Render → *Events* → previous deploy → *Rollback*.
* Logs: Render → service → *Logs* (`LOG_CHANNEL=stderr`).
* Run a command: Render → service → *Shell* (paid plans) e.g. `php artisan lms:recompute-overdue`.

## 7. Free-plan limits (read this)
* **Uploaded files are lost on every deploy/restart** (free web services have an ephemeral disk): e-book PDFs and cover images disappear. For real use choose a paid web plan, uncomment the `disk:` block in `render.yaml` (mounted at `/var/www/html/storage/app`) – or move uploads to S3.
* **Free Postgres expires after 30 days.** Upgrade it before then or export a backup.
* **Free web services sleep when idle** (first request is slow) and **cannot send SMTP mail**. Keep `MAIL_MAILER=log`, or use a paid plan / an HTTP-API mail provider.
* **Scheduled jobs** (`lms:recompute-overdue`, `lms:daily-digest`) need a Render *Cron Job* (paid). Without it, the overdue notification is still generated each day when staff sign in.

## 8. Troubleshooting
| Symptom | Fix |
|---|---|
| Build fails on `composer install` | `composer.lock` not committed, or lock needs PHP ≥ 8.3 (image is 8.4 – fine) |
| 500 on every page | `APP_KEY` missing/wrong format → must start with `base64:` |
| Pages load, images/CSS missing or "mixed content" | `bootstrap/app.php` must contain `trustProxies(at: '*')` (included) and `APP_URL` must be `https://…` |
| Redirect loop / 404 on routes | `.htaccess` not honoured – the Dockerfile enables `AllowOverride All`; do not remove it |
| "SQLSTATE … could not connect" | database and web service must be in the **same region**; `DB_URL` comes from the `lms-db` link in `render.yaml` |
| Deploy job fails "Secret RENDER_DEPLOY_HOOK_URL is missing" | add the GitHub secret (step 5) |
| Nothing deploys after push | Auto-Deploy is Off by design – check the *Deploy to Render* job ran (needs tests green) |
