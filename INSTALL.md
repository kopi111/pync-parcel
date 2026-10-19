# Pync Parcel Chateau — Installation Guide

A PHP + MySQL parcel-forwarding web application.

---

## 1. Requirements

| Component | Version |
|-----------|---------|
| PHP       | 8.1 or newer (tested on 8.3) |
| Database  | MySQL 8.x **or** MariaDB 10.4+ |
| Web server| Apache or Nginx (or `php -S` for local testing) |
| Composer  | optional — only if `vendor/` is not already bundled |

**Required PHP extensions:** `mysqli`, `mbstring`, `openssl`, `curl`, `fileinfo`.

> `curl` is needed for the warehouse-sync integration. If it is missing, the app
> still runs and registration still works — the warehouse push is skipped
> gracefully — but customers will not be synced to the warehouse API.

---

## 2. Install the files

Extract the archive into your web root (e.g. `/var/www/pyncparcel` or
`public_html` on shared hosting).

---

## 3. Create the database

Create a database and a user, then import the bundled schema + seed:

```sql
CREATE DATABASE pyncparcel CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER 'pyncuser'@'localhost' IDENTIFIED BY 'CHANGE_ME';
GRANT ALL PRIVILEGES ON pyncparcel.* TO 'pyncuser'@'localhost';
FLUSH PRIVILEGES;
```

```bash
mysql -u pyncuser -p pyncparcel < install.sql
```

`install.sql` creates all 8 tables and a default admin account.

> On cPanel/phpMyAdmin: create the DB + user in the panel, then **Import**
> `install.sql` into the new database.

---

## 4. Configure the environment

Copy the template and fill in your values:

```bash
cp .env.example .env
```

Edit `.env`:

```ini
APP_ENV=production           # 'development' shows errors; 'production' hides them
DB_HOST=localhost
DB_USER=pyncuser
DB_PASS=CHANGE_ME
DB_NAME=pyncparcel

# Email (PHPMailer / SMTP). Leave MAIL_USERNAME/PASSWORD empty for an
# unauthenticated relay; set MAIL_ENCRYPTION=tls (or ssl) for a real provider.
MAIL_HOST=smtp.yourprovider.com
MAIL_PORT=587
MAIL_USERNAME=no-reply@yourdomain.com
MAIL_PASSWORD=your-smtp-password
MAIL_ENCRYPTION=tls

# Google reCAPTCHA v2 (set RECAPTCHA_ENABLED=true to enforce)
RECAPTCHA_ENABLED=false
RECAPTCHA_SITE_KEY=
RECAPTCHA_SECRET_KEY=

# Warehouse API (optional; requires php-curl)
WAREHOUSE_API_URL=
WAREHOUSE_API_KEY=
```

> **Never commit `.env`** — it holds live credentials. It is already in
> `.gitignore`.

---

## 5. Install PHP dependencies

If `vendor/` is **not** bundled in the archive:

```bash
composer install --no-dev
```

(If `vendor/` is present, skip this step.)

---

## 6. Web server & permissions

Point the document root at the project folder. Ensure these are writable by the
web-server user:

```bash
chmod -R 775 uploaded-file logs
```

**Local testing** (no Apache needed):

```bash
php -S localhost:8055 -t .
# then browse http://localhost:8055/
```

---

## 7. First login

| Field | Value |
|-------|-------|
| URL   | `/sign-in.php` |
| Email | `admin-ppc@pyncparcel.com` |
| Password | `admin123` |

**Change the admin password immediately** after the first login (My Account → Password).

---

## 8. Security notes

- Sessions use secure, HTTP-only cookies; identity lives in the server session
  (no client-side identity cookie).
- All state-changing forms are CSRF-protected; mutating endpoints reject requests
  without a valid token (HTTP 419).
- User input is escaped on output and queries on the auth path use prepared
  statements.
- Set `APP_ENV=production` in `.env` on live servers so internal errors are not
  shown to visitors.

---

## File reference

| File | Purpose |
|------|---------|
| `install.sql` | Database schema + default admin (run once) |
| `.env.example` | Environment template — copy to `.env` |
| `config.php` | Loads `.env`, opens the DB connection, starts the secure session |
| `database_schema.sql` | Original reference schema (use `install.sql` to install) |
