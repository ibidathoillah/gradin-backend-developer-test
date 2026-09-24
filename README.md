# Gradin Backend Developer Test — Courier API

A small Laravel REST API implementing the Courier master-data module from the Gradin backend developer test.

## Requirements

- PHP 8.3+
- Composer
- SQLite, MySQL, or PostgreSQL

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve
```

## Courier model

Each courier has a name, level (1–5), optional email and phone number, active state, and timestamps. Database constraints/indexes are included for commonly queried fields.

## API

- `GET /api/couriers` — paginated list, default sorted by courier name
- `POST /api/couriers` — create a courier with validation
- `GET /api/couriers/{id}` — show a courier
- `PUT/PATCH /api/couriers/{id}` — update with validation
- `DELETE /api/couriers/{id}` — delete a courier

### Index query options

```text
?search=budi+agung
?level=2,3
?sort=created_at&direction=desc
?sort=registered_at&direction=asc
?per_page=25
```

Search is token-based and uses AND semantics, so `budi agung` matches a name such as `Budiono Hadi Agung`. Invalid level values are ignored; valid levels are restricted to 1–5. Pagination is capped at 100 rows per page.

## Validation

`name` is required and limited to 255 characters. `level` is required and must be an integer from 1 through 5. Email is optional but must be valid and unique. Phone is optional and limited to 30 characters. Updates support partial payloads while applying the same validation rules.

## Tests

```bash
php artisan test
vendor/bin/pint --test
```

Feature tests cover pagination/default sorting, partial multi-term search, level filtering, registration-date sorting, create validation/persistence, show, update validation/persistence, and delete persistence. GitHub Actions runs the full suite and Pint on every push and pull request.
