# Ceysaid CRM Production Deployment Runbook

This runbook deploys the Laravel application to the Ubuntu server at `/var/www/Ceysaid-Dashboard`.

For the finance release described in `FINANCE_ENHANCEMENT_SCOPE.md`:

- Git branch: `codex/ui-redesign`
- Expected commit: `c50cfd0`
- Application path: `/var/www/Ceysaid-Dashboard`
- Known server address: `13.201.194.56`
- SSH user used previously: `ubuntu`
- SSH key used previously: `cesaid-ec2.pem`

The deployment includes database migrations. Take a database backup before starting.

## 1. Pre-deployment checklist

Confirm all of the following:

- The deployment key `cesaid-ec2.pem` is available and has permission `400`.
- The current SSH fingerprint has been verified with whoever manages the EC2 instance.
- The release commit is available on GitHub.
- A short maintenance window has been communicated to users.
- No long-running import, payment entry, or ticket-issuing process is running.
- You know which URL is the real production URL: `https://crm.ceysaid.lk` or the server IP.

From the local project, verify the release:

```bash
cd /Applications/work/my/Ceysaid
git status --short
git diff --exit-code
git diff --cached --exit-code
git log -1 --oneline
php artisan test
npm run build
```

Expected release commit:

```text
c50cfd0 Expand finance registers with expense categories and transfer filters
```

Review `git status --short`; the newly created runbook may appear until you commit it. Both `git diff --exit-code` commands must succeed, and no application source changes may be pending. The deployment itself pulls the already-published release commit from GitHub.

## 2. Connect to the server

From the directory containing the PEM file:

```bash
chmod 400 ./cesaid-ec2.pem
ssh -i ./cesaid-ec2.pem ubuntu@13.201.194.56
```

If SSH reports that the host key changed, stop and verify the new fingerprint before removing or bypassing the old key. At the time this runbook was written, the server returned this ED25519 fingerprint:

```text
SHA256:Z3dGCLLwOMyxxEiEfXZYd5ce+vXMlc1yoaCzFkopnXc
```

Do not use `StrictHostKeyChecking=no` for production.

## 3. Inspect the current production state

```bash
cd /var/www/Ceysaid-Dashboard
pwd
git status --short
git branch --show-current
git log -1 --oneline
php -v
composer --version
node --version
npm --version
```

Expected PHP version: PHP 8.2, 8.3, or 8.4.

If `git status --short` shows modified or untracked application files, stop. Back up and review them before continuing. Do not discard production changes with `git reset --hard`.

### Existing production-generated changes

An older deployment may show modified `composer.lock`, published Filament assets, or `.gitignore` files, together with untracked `storage/framework/sessions/`. If this occurs, first complete the backups in step 4 and enable maintenance mode in step 5. Then return here and preserve the tracked changes before switching release branches:

```bash
cd /var/www/Ceysaid-Dashboard
sudo mkdir -p /var/backups/ceysaid
sudo chmod 700 /var/backups/ceysaid
git diff --stat
git diff > /tmp/ceysaid-pre-deploy-working-tree.patch
sudo mv /tmp/ceysaid-pre-deploy-working-tree.patch /var/backups/ceysaid/
git stash push -m "pre-finance-deploy-production-generated-files-2026-10-12"
git stash list -1
git status --short --untracked-files=no
```

Do **not** add `-u` or `--include-untracked` to the stash command. The untracked session files are active Laravel runtime data; moving or deleting them can log users out. It is acceptable for `storage/framework/sessions/` to remain visible in `git status`. The tracked-files-only status command must be clean before switching branches.

Do not apply this stash to the new release. Keep it temporarily as a recovery record and remove it only after the deployment has been fully verified.

Record the current commit for rollback:

```bash
git rev-parse HEAD
```

Copy that value somewhere outside the terminal and label it `PREVIOUS_COMMIT`.

## 4. Back up production

Create a protected deployment backup directory:

```bash
sudo mkdir -p /var/backups/ceysaid
sudo chmod 700 /var/backups/ceysaid
```

Back up the environment configuration:

```bash
sudo cp /var/www/Ceysaid-Dashboard/.env /var/backups/ceysaid/env-before-finance-release
```

Back up uploaded files:

```bash
sudo tar -czf /var/backups/ceysaid/storage-before-finance-release.tar.gz \
  -C /var/www/Ceysaid-Dashboard storage/app
```

Read `DB_HOST`, `DB_DATABASE`, and `DB_USERNAME` from `.env`, then create a MySQL backup. Enter the password only at the prompt:

```bash
mysqldump --single-transaction --routines --triggers \
  -h YOUR_DB_HOST \
  -u YOUR_DB_USERNAME \
  -p YOUR_DB_DATABASE \
  | gzip > /var/backups/ceysaid/database-before-finance-release.sql.gz
```

Verify the backup is not empty:

```bash
ls -lh /var/backups/ceysaid/database-before-finance-release.sql.gz
gzip -t /var/backups/ceysaid/database-before-finance-release.sql.gz
```

Do not continue if the database backup fails.

## 5. Enable maintenance mode

```bash
cd /var/www/Ceysaid-Dashboard
php artisan down --retry=60 --refresh=15
```

If maintenance mode fails because of permissions, fix that issue before changing the code.

## 6. Download the release

Fetch the remote branch:

```bash
git fetch origin
git show --no-patch --oneline origin/codex/ui-redesign
```

It must show commit `c50cfd0` for this release.

If the branch already exists locally:

```bash
git switch codex/ui-redesign
git pull --ff-only origin codex/ui-redesign
```

If Git says the branch does not exist locally:

```bash
git switch --track origin/codex/ui-redesign
```

Verify the checked-out release:

```bash
git status --short
git log -1 --oneline
```

The status must be clean and the current commit must be `c50cfd0`.

## 7. Install PHP dependencies

```bash
composer install \
  --no-dev \
  --prefer-dist \
  --optimize-autoloader \
  --no-interaction
```

Do not run `composer update` on production.

## 8. Install frontend dependencies and build assets

The server previously failed because npm did not install Rollup and Lightning CSS Linux optional packages. Never copy a local or macOS `node_modules` directory to this server.

Remove only this application's dependency directory, after confirming the path:

```bash
cd /var/www/Ceysaid-Dashboard
pwd
sudo rm -rf /var/www/Ceysaid-Dashboard/node_modules
npm cache verify
npm ci --include=optional
npm run build
```

The build must finish with `✓ built` and create `public/build/manifest.json`.

Verify:

```bash
test -f public/build/manifest.json && echo "Vite build is present"
```

### If Rollup is still missing

For this exact release, install its locked Linux binary and rebuild:

```bash
npm install --no-save @rollup/rollup-linux-x64-gnu@4.45.1
npm run build
```

### If Lightning CSS is still missing

For this exact release, install its locked Linux binary and rebuild:

```bash
npm install --no-save lightningcss-linux-x64-gnu@1.30.1
npm run build
```

### If Tailwind Oxide reports a missing native binding

For this exact release, install the matching locked Tailwind Linux binary and rebuild:

```bash
npm install --no-save --package-lock=false @tailwindcss/oxide-linux-x64-gnu@4.1.11
npm run build
```

Do not delete `package-lock.json`. It is the production dependency lock and is required by `npm ci`.

## 9. Run database migrations

Review pending migrations:

```bash
php artisan migrate:status
```

Run them in production mode:

```bash
php artisan migrate --force
```

This release creates:

- `financial_accounts`
- `expense_categories`
- `internal_transfers`
- `expenses.category_id`

It also imports existing free-text expense categories into `expense_categories`.

Do not run `migrate:fresh`, `db:wipe`, or any seed command on production.

## 10. Refresh Laravel caches

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan storage:link
php artisan queue:restart
```

`storage:link` may say that the link already exists; that is acceptable.

## 11. Check writable permissions

Normally existing permissions should be preserved. Verify the two writable locations:

```bash
ls -ld storage bootstrap/cache
```

If Laravel reports permission errors, apply group write access without making the whole application writable:

```bash
sudo chgrp -R www-data /var/www/Ceysaid-Dashboard/storage /var/www/Ceysaid-Dashboard/bootstrap/cache
sudo chmod -R ug+rwX /var/www/Ceysaid-Dashboard/storage /var/www/Ceysaid-Dashboard/bootstrap/cache
```

Do not use `chmod -R 777`.

## 12. Validate the web server and PHP

Identify the active web server:

```bash
systemctl is-active apache2 || true
systemctl is-active nginx || true
systemctl list-units --type=service 'php*-fpm.service'
```

For Apache:

```bash
sudo apache2ctl configtest
sudo systemctl reload apache2
```

For Nginx with PHP-FPM:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

Reload the active PHP-FPM service if one is listed. For example:

```bash
sudo systemctl reload php8.3-fpm
```

Use the version actually installed on the server; do not assume it is `php8.3-fpm`.

## 13. Bring the application online

```bash
cd /var/www/Ceysaid-Dashboard
php artisan up
```

## 14. Health checks

Check the local web server first:

```bash
curl -I http://127.0.0.1/
```

Check the real public URL:

```bash
curl -I https://crm.ceysaid.lk/
```

Expected result: HTTP `200` or an intentional redirect to HTTPS/login.

Check Laravel logs:

```bash
tail -n 100 storage/logs/laravel.log
```

Check failed background jobs:

```bash
php artisan queue:failed
```

Log in as an administrator or accountant and verify:

1. Dashboard loads without JavaScript or CSS errors.
2. Finance → Payment Register opens.
3. Finance → Expenses opens and existing categories are visible.
4. Finance → Bank Statements opens.
5. Finance → Internal Transfers opens.
6. Vendor bills display linked invoice numbers.
7. Supplier payment detail has the **Edit payment** action.
8. Excel downloads open as `.xlsx` files.
9. Existing invoice, vendor bill, air-ticket, and visa records still load.

## 15. Rollback procedure

Rollback if the application cannot serve requests, migrations fail, or finance totals are incorrect.

### 15.1 Put the application back into maintenance mode

```bash
cd /var/www/Ceysaid-Dashboard
php artisan down --retry=60
```

### 15.2 Restore the previous code

Use the exact commit recorded as `PREVIOUS_COMMIT` in step 3:

```bash
git switch codex/ui-redesign
git reset --hard PREVIOUS_COMMIT_VALUE
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
sudo rm -rf /var/www/Ceysaid-Dashboard/node_modules
npm ci --include=optional
npm run build
```

Before running `git reset --hard`, confirm you are inside `/var/www/Ceysaid-Dashboard` and that no production-only changes need to be preserved.

### 15.3 Restore the database

If no users entered data after deployment, the finance migration can be reversed with:

```bash
php artisan migrate:rollback --step=1 --force
```

Do not run that command after users have started entering expense categories, transfers, or edited supplier payments; it would remove new finance data. In that situation, restore the verified database backup instead:

```bash
gunzip -c /var/backups/ceysaid/database-before-finance-release.sql.gz \
  | mysql -h YOUR_DB_HOST -u YOUR_DB_USERNAME -p YOUR_DB_DATABASE
```

### 15.4 Refresh caches and reopen

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan up
```

Repeat the health checks in step 14.

## 16. Deployment record

Record the following after each deployment:

```text
Deployment date/time:
Deployed by:
Server:
Public URL:
Previous commit:
New commit:
Database backup path:
Storage backup path:
Migration result:
Asset build result:
Health-check result:
Rollback required: Yes / No
Notes:
```

