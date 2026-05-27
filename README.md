# Smart Maheshkhali — Pilot Registration Portal

A lightweight PHP + SQLite application that collects free-school-automation
registration applications for the **Smart Maheshkhali** pilot programme
(Maheshkhali upazila, Bangladesh) and provides a password-protected admin
dashboard for reviewing, editing, exporting and moderating submissions.

The UI is bilingual (Bangla / English) and uses Tiro Bangla + Inter from
Google Fonts. No build step, no JavaScript framework, no Composer
dependencies — drop the folder on any LAMP-style host and it runs.

---

## Features

**Public site (`/`)**
- Multi-step bilingual application form with progress bar
- HTML5 geolocation capture for school coordinates
- Subdomain availability hint and reserved-name protection
- Honeypot + per-IP submission cool-down (anti-spam)
- Form repopulates on validation errors (no lost typing)
- "Registration closed" landing page when admin disables intake

**Admin (`/admin`)**
- Password-protected login with bcrypt hashes (no plaintext passwords)
- One-click toggle to open / close public registration
- Per-category quota counters (primary / madrasah / high school)
- Searchable, filterable, paginated registration table
- View / Edit / Delete actions (delete is POST + CSRF protected)
- CSV export of all registrations
- Auto-displayed submission timestamps

**Hardening**
- CSRF tokens on every state-changing form
- Output escaping, no double-encoding of Bangla input
- Security headers + sensitive-file blocks via `.htaccess`
- Production-mode error suppression (no DB error leaks)
- Session cookies marked `HttpOnly`, `SameSite=Lax`, `Secure` on HTTPS
- DB-level uniqueness on subdomain
- Pilot quotas enforced server-side, not just displayed

---

## Requirements

- PHP **7.4+** (tested on 8.4)
- Extensions: `pdo_sqlite`, `session`, `filter`, `mbstring`
- Apache with `mod_rewrite` enabled (for the clean URLs in `.htaccess`).
  Nginx works too — see [Nginx notes](#nginx).

---

## Installation

```bash
git clone https://github.com/academicschoolbd/initiative.git
cd initiative

# 1. Create local configuration
cp config.example.php config.php

# 2. Generate an admin password hash and paste it into config.php
php -r "echo password_hash('CHANGE_ME_to_a_strong_password', PASSWORD_DEFAULT), PHP_EOL;"

# 3. Make sure the directory is writable for the SQLite file
chmod 775 .
```

Then point your web server's document root at the project folder, or run
locally with the built-in server:

```bash
php -S 127.0.0.1:8000
```

Open <http://127.0.0.1:8000/index.php> for the public form and
<http://127.0.0.1:8000/admin.php> for the dashboard.

> The schema is created automatically on first request — no manual SQL
> migration is needed.

### Configuration keys (`config.php`)

| Key | Description |
| --- | --- |
| `app_env` | `production` hides PHP errors; `development` shows them. |
| `app_debug` | Extra debug output in `development`. |
| `base_path` | URL prefix the app is mounted at, e.g. `/initiative`. Use `''` if at the domain root. |
| `admin_username` | Login name for the admin dashboard. |
| `admin_password_hash` | bcrypt hash of the admin password. |
| `quotas` | Per-institution quotas. The form rejects new submissions for a full category. |
| `submission_cooldown_seconds` | Minimum seconds between submissions from the same IP. |
| `force_https` | When `true`, plain HTTP requests are redirected to HTTPS. |
| `sqlite_path` | Filesystem path to the SQLite database. |

### Apache

`.htaccess` is shipped with sane defaults: clean URLs, security headers,
and blocks for `config.php`, `*.sqlite`, the `includes/` folder, and the
example config. Make sure `AllowOverride All` is set for the directory
in your vhost.

### cPanel / shared hosting (File Manager upload)

The whole project is plain PHP files plus `.htaccess` — no Composer, no
build step, no Node. Steps:

1. **Upload** the contents of this folder into your hosting account
   using cPanel **File Manager** → *Upload*.
   - Most often you will land in `public_html/`. To serve the app at
     `https://yourdomain.tld/` upload directly into `public_html/`.
     To serve it at `https://yourdomain.tld/initiative/` create the
     `initiative/` subfolder first and upload there.
   - cPanel hides dotfiles by default. Tick *Settings → Show Hidden
     Files (dotfiles)* before uploading so `.htaccess` and `.gitignore`
     come along.

2. **Create `config.php`** from the template using *File Manager →
   New File* (or upload it). Inside `config.php` set:

   ```php
   'base_path'           => '',                 // '' if served at domain root
                                                // '/initiative' if in subfolder
   'admin_password_hash' => '$2y$12$...',       // bcrypt hash of your password
   'whatsapp_contact'    => '+8801XXXXXXXXX',   // your WhatsApp number
   'force_https'         => true,               // most cPanel hosts have a free LE cert
   ```

   To generate the bcrypt hash, open *Terminal* in cPanel (or use a
   local PHP) and run:
   ```bash
   php -r "echo password_hash('your-strong-password', PASSWORD_DEFAULT), PHP_EOL;"
   ```

3. **Match `.htaccess` `RewriteBase`** to `base_path`. The shipped file
   has `RewriteBase /initiative/`. If you uploaded to the domain root,
   change that line to `RewriteBase /`. If your subfolder has a
   different name, replace `initiative` with that name.

4. **Permissions.** cPanel typically gives files `0644` and folders
   `0755`, which is fine. The application creates `database.sqlite` on
   first request, so the *folder containing the project* must be
   writable by the PHP user. If you see a "Database unavailable"
   message, right-click the project folder in File Manager →
   *Change Permissions* and ensure it is `755` (or `775` on some
   hosts that run PHP as a different user).

5. **Verify.** Open `https://yourdomain.tld/` — you should see the
   sponsor splash dialog and then the registration form. Open
   `https://yourdomain.tld/admin.php` and log in with the password you
   set in step 2.

6. **Sanity-check security.** From any browser run:
   ```
   curl -I https://yourdomain.tld/config.php
   curl -I https://yourdomain.tld/database.sqlite
   curl -I https://yourdomain.tld/includes/auth.php
   ```
   All three should return `403`. If any returns `200`, your host is
   not honouring `.htaccess` — talk to support before going live.

### Nginx

`.htaccess` is ignored by Nginx. Equivalent rules:

```nginx
server {
    listen 80;
    server_name your-host;
    root /var/www/initiative;
    index index.php;

    location / {
        try_files $uri $uri.php $uri/ =404;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # Block sensitive files
    location ~* \.(sqlite|sqlite-journal)$ { deny all; }
    location ~* /(config\.php|config\.example\.php)$ { deny all; }
    location ^~ /includes/ { deny all; }

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
}
```

---

## Project layout

```
initiative/
├── .htaccess              # Apache rewrite + security headers + file blocks
├── .gitignore
├── README.md
├── config.example.php     # template — copy to config.php
├── config.php             # local secrets (git-ignored)
├── database.php           # PDO bootstrap + idempotent migrations
├── includes/
│   ├── bootstrap.php      # config loader, error handling, session, headers
│   ├── auth.php           # admin login helpers
│   ├── csrf.php           # token issue / verify
│   └── helpers.php        # escaping, pagination, redirects
├── index.php              # public registration form
├── submit.php             # form processor (validation, quota, dedupe)
├── success.php            # post-submit confirmation
├── login.php              # admin login form
├── logout.php             # destroys admin session
├── admin.php              # admin dashboard
├── admin-action.php       # view / edit / delete handlers
└── 404.php                # fallback page
```

---

## Security notes

- `config.php` and `*.sqlite` are git-ignored and blocked at the web
  server level. Always verify with `curl -I https://your-host/config.php`
  that they return `403`.
- The admin password is stored as a bcrypt hash; the plaintext never
  reaches disk in this repo.
- All state-changing requests (toggle / delete / edit / login) require a
  matching CSRF token.
- PHP errors are hidden in `production` mode. Tail your server's PHP
  error log for diagnostics.
- After a successful login, the session ID is regenerated to mitigate
  session-fixation attacks.

If you discover a vulnerability, please open a private security advisory
on GitHub rather than a public issue.

---

## License

This project ships without an explicit license. Add one before public
distribution if that matters for your deployment.
