# Inventory Management API — Laravel 11

REST API for inventory management. Migrated from **Laravel 8 / PHP 7.4** to **Laravel 11 / PHP 8.2** with Docker, Sanctum authentication, Swagger documentation, and Laravel Telescope (local).

---

## Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 11 |
| PHP | 8.2-fpm |
| Auth | Laravel Sanctum (bearer token) |
| DB | MySQL 8.0 |
| Web server | nginx 1.25-alpine |
| Docs | L5-Swagger / OpenAPI 3 |
| Debugging | Laravel Telescope (local only) |
| Tests | PHPUnit 11 |

---

## Project Structure

This project is split across two repositories that must be sibling folders:

```
parent-folder/
├── backend-laravel/        ← this repo
└── frontend-vue3/          ← frontend repo (separate)
```

---

## Quick Start with Docker

### 1. Clone both repositories as sibling folders

```bash
git clone https://github.com/HaroldATdev/backend-laravel11.git backend-laravel11
git clone https://github.com/HaroldATdev/frontend-vue3.git frontend-vue3
```

### 2. Start everything

```bash
cd backend-laravel
docker compose up -d --build
```

The entrypoint script runs automatically on first boot:
1. Copies `.env.example` → `.env` (if `.env` doesn't exist)
2. Generates `APP_KEY`
3. Waits for MySQL to be ready
4. Runs `php artisan migrate`
5. Generates Swagger docs
6. Starts PHP-FPM

> The `.env.example` already includes `FRONTEND_PATH=../frontend-vue3`. If you cloned the frontend under a different name, edit that variable before starting.

### 3. Run migrations and seed data

Migrations run automatically. To also load the seed data (100 categories, 10 000 products, 30 000 stock movements):

```bash
docker compose exec backend php artisan migrate --seed
```

### Services started

| Container | Role | Port (host) |
|---|---|---|
| `inventory_backend` | PHP 8.2-FPM | — (internal) |
| `inventory_nginx` | Web server / reverse proxy | **8080** |
| `inventory_frontend` | Vue dev server (Vite/npm) | **5173** |
| `inventory_mysql` | MySQL 8.0 | 3307 |

| URL | Description |
|---|---|
| http://localhost:5173 | Frontend (Vue 3 / Vite) |
| http://localhost:8080/api | REST API |
| http://localhost:8080/api/documentation | Swagger UI |
| http://localhost:8080/telescope | Laravel Telescope (local only) |

---

## Endpoints

| Method | URL | Auth | Description |
|---|---|---|---|
| POST | `/api/login` | ✗ | Get token |
| GET | `/api/health` | ✗ | Health check |
| GET | `/api/me` | ✓ | Current user |
| POST | `/api/logout` | ✓ | Revoke token |
| GET | `/api/dashboard` | ✓ | Stats + low stock + last movements |
| GET/POST | `/api/products` | ✓ | List (paginated/filterable) / Create |
| GET/PUT/DELETE | `/api/products/{id}` | ✓ | Show / Update / Delete |
| GET/POST | `/api/products/{id}/stock-movements` | ✓ | List / Register movement |
| GET/POST | `/api/categories` | ✓ | List (paginated/filterable) / Create |
| GET/PUT/DELETE | `/api/categories/{id}` | ✓ | Show / Update / Delete |

### Product filter parameters

`name`, `category_id`, `status`, `price_min`, `price_max`, `stock_min`, `stock_max`, `sort` (name/price/stock/created_at), `direction` (asc/desc), `per_page`

---

## Credentials (after seed)

```
email:    admin@inventory.test
password: password
```

---

## Swagger UI

```
http://localhost:8080/api/documentation
```

Authenticate: click **Authorize** → enter `Bearer <your_token>`.

---

## Laravel Telescope

Available only in `APP_ENV=local`:

```
http://localhost:8080/telescope
```

---

## Running Tests

```bash
# Inside the container
docker compose exec backend php artisan test

# Or locally with PHP 8.2
php artisan test
```

All tests use an **in-memory SQLite** database — no MySQL required.

---

## Migration from Laravel 8 — Changes Summary

### Breaking changes addressed

| Legacy issue | Fix applied |
|---|---|
| Plain `api_token` column (SQL injection risk) | **Laravel Sanctum** + `personal_access_tokens` table |
| Raw SQL with string concatenation | Eloquent scopes with parameterized queries |
| N+1 queries in product listing | `with('category')` eager loading |
| No pagination (unlimited result sets) | `paginate()` on all list endpoints |
| No database indexes | Indexes on `name`, `status`, `price`, `stock`, `created_at`, FK columns |
| No FK constraints | `foreignId()->constrained()` on all relations |
| Inline validation in controllers | Form Requests (`app/Http/Requests/`) |
| Inconsistent JSON responses | Standardized `success()` / `error()` helpers in base Controller |
| Stock updates without transactions | `DB::transaction()` + `lockForUpdate()` (race condition prevention) |
| No audit trail | `AuditLog` model + `AuditService::log()` on every mutation |
| No caching | `Cache::remember()` (60 s) for dashboard stats |
| No documentation | Swagger / OpenAPI via L5-Swagger annotations |
| Named classes in migrations | Anonymous migration classes (L11 convention) |
| Providers array in `config/app.php` | `ServiceProvider::defaultProviders()->merge()` |
| `bootstrap/app.php` L8 style | L11 `Application::configure()` bootstrap |
| Seeder with 1-by-1 inserts | Chunked bulk inserts (500 rows/batch) |

---

## Docker Services

| Service | Container | Exposed port |
|---|---|---|
| PHP-FPM (backend) | `inventory_backend` | 9000 (internal) |
| nginx | `inventory_nginx` | **8080** |
| MySQL 8 | `inventory_mysql` | **3307** |

MySQL has a **healthcheck** — the backend container waits for `service_healthy` before starting.

### Useful commands

```bash
# Stream logs
docker compose logs -f backend

# Open a shell
docker compose exec backend bash

# Stop services
docker compose down

# Stop + delete volumes (wipes DB)
docker compose down -v
```

---

## Project Structure

```
app/
  Http/
    Controllers/      # Thin controllers with Swagger @OA annotations
    Requests/         # Form Request validation (Auth/, Category/, Product/, Stock/)
    Resources/        # API Resources (CategoryResource, ProductResource, ...)
  Models/             # Eloquent models with scopes, casts, relations
  Services/           # Business logic (AuthService, CategoryService, ...)
  Providers/          # AppServiceProvider (registers Telescope in local env)
database/
  factories/          # Model factories for tests
  migrations/         # Anonymous class migrations (L11 convention)
  seeders/            # Chunked bulk inserts (500 rows/batch)
docker/
  nginx/nginx.conf    # nginx site config (port 80, FastCGI to backend:9000)
  php/Dockerfile      # PHP 8.2-fpm with pdo_mysql, opcache, composer 2.7
  php/php.ini         # memory 256M, opcache enabled
tests/Feature/        # AuthTest, CategoryTest, ProductTest (RefreshDatabase + SQLite)
```

