# AeroBook

AeroBook is an online airline ticketing system built with core PHP, MySQL, PDO, HTML, CSS, JavaScript, and a small custom MVC foundation.

## Requirements

- PHP 8.1+ with PDO MySQL enabled
- MySQL 8.0.16+ (for enforced `CHECK` constraints)
- Apache with `mod_rewrite` (or another web server configured to route requests to `public/index.php`)

## Setup

1. Copy `.env.example` to `.env` and set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` for your local MySQL instance.
2. Create the database named in `DB_DATABASE` using `utf8mb4` / `utf8mb4_unicode_ci`.
3. Select that database, then import `database/schema.sql` followed by `database/seed.sql`.
4. Configure the web server document root to the project's `public/` directory. For a Laragon virtual host pointed at the project root, the root `.htaccess` forwards requests into `public/` instead.

The front controller loads environment configuration from `.env`. Point the web server at `public/` so application code and local environment files stay outside the document root. The root `.htaccess` also disables directory listings and blocks direct access to the private source/configuration folders when the Laragon virtual host uses the project root.

For an existing database created from an older schema, apply each migration in `database/migrations/` in numeric order, skipping migrations already applied. The current `schema.sql` already includes those migrated columns for fresh installs. Set `APP_TIMEZONE` to the application's local timezone; it defaults to `Asia/Karachi` and is also used to align PHP and MySQL date/time operations.

## Authentication

- Customer registration and sign-in: `/register` and `/login`
- Sign-in for all accounts: `/login` (admins go to `/admin`, customers go to `/account`)
- Sign-out is submitted as a CSRF-protected POST form.
- Customer and admin access is protected by role middleware, which also checks that the account is still active.

Customer registration always assigns the `customer` role. To provision an administrator, first register that person as a customer, then promote the exact account from HeidiSQL or another trusted database client:

```sql
UPDATE users SET role = 'admin' WHERE email = 'admin@example.com';
```

Use the matching email in place of the example and confirm the query changes exactly one row. Passwords are stored with PHP `password_hash()` and checked with `password_verify()`. For deployment, serve the site over HTTPS so session cookies use the secure flag.
