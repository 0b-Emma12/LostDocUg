# LostDocUg

LostDocUg is a privacy-first document recovery platform in early development. The current slice provides verified accounts, encrypted drafts, secure report submission, private image storage, and an owner-only dashboard. OCR, automated matching, claims, and recovery are not yet implemented.

## Requirements

- PHP 8.3+ with PDO SQLite for local tests, PDO PostgreSQL for PostgreSQL, and GD with WebP support for image processing
- Composer
- Node.js and npm
- PostgreSQL 16+ for a PostgreSQL development database

## Local setup

Copy `.env.example` to `.env`, set a unique local `APP_KEY` with `php artisan key:generate`, and configure PostgreSQL credentials. Enable `pdo_pgsql` before setting `DB_CONNECTION=pgsql`. Enable GD with WebP support for image uploads. If GD is installed but not enabled in the active PHP configuration, start the local server with `php -d extension=gd artisan serve`. Verification mail uses the local log mailer by default; inspect `storage/logs/laravel.log` for the verification link. Local private uploads use `storage/app/private/documents`; production can select `PRIVATE_DOCUMENTS_DISK=private_documents_s3` after configuring private S3 credentials.

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
npm install
npm run build
php artisan migrate
php artisan serve
```

The local test suite uses in-memory SQLite regardless of `.env`. If GD is installed but not enabled in the active PHP CLI configuration, temporarily run tests with `php -d extension=gd vendor/bin/phpunit` without changing the machine's `php.ini`.

```powershell
php artisan test
```

## Implemented foundation and reporting

- Backed enums for report kind, document kind, report status, and account role.
- Reports with random public references, enum casts, indexes, constrained values, and restrictive owner deletion.
- Audit events with retained actor IDs, separate request context fields, and append-only database protection.
- Report policy defaults to owner-only access. Reviewer access requires a future assignment workflow; a role alone does not grant access to every sensitive report.
- Authentication uses Laravel sessions and signed email verification. Reports and drafts require a verified account.
- Alpine.js is bundled locally with Vite; there is no Livewire dependency.
- Draft payloads use Laravel's `encrypted:array` cast and accept only allow-listed form fields.
- Image uploads are checked, resized, stripped of embedded metadata, converted to WebP, deduplicated, and stored on a private disk. Bank-card image uploads are rejected server-side.
- Images are streamed through an authenticated, owner-authorized route. No public object URL is created.
- Database writes use per-account idempotency keys and clean up stored objects if report creation rolls back.

## Privacy boundaries

Never add public identity searches or document galleries. Never collect payment-card credentials. Keep report images in private storage and authorize every sensitive action on the server. OCR output and automated matches are not proof of ownership or authenticity. Configure and test retention/deletion before launch. Obtain Ugandan privacy/legal review before a public pilot; do not assume institutional integrations are available.
