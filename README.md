**KHQR License Store**
- Minimal PHP 8.2 + Apache app to sell license keys via KHQR (Bakong).
- MySQL database with migrations managed by Phinx.
- Admin dashboard to manage products, license keys, and orders.

**What You’ll Find Here**
- Database dump to import on shared hosting: `database/khqr_store.sql`.
- Docker setup for local development: `docker-compose.yml`, `Dockerfile`.
- Phinx config and migrations: `phinx.php`, `migrations/`.
- Public web root: `public/`.

**Requirements**
- PHP 8.2+
- MySQL 5.7+/8.0
- Composer 2.x (if running without Docker)
- Docker Desktop (if running with Docker)

**Clone The Repo**
- `git clone https://github.com/MyKhode/php-composer-khqr.git`
- `cd php-composer-khqr`

**Environment (.env)**
- Create `.env` in the project root (copy from a sample if available) and set:
  - `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
  - `ADMIN_DEFAULT_USERNAME`, `ADMIN_DEFAULT_PASSWORD`
  - `BAKONG_DEV_TOKEN`, `BAKONG_BANK_ACCOUNT`, `BAKONG_MERCHANT_NAME`, `BAKONG_MERCHANT_CITY`, `BAKONG_APP_NAME`, `BAKONG_APP_ICON_URL`, `BAKONG_CALLBACK_URL`, `BAKONG_DEFAULT_CURRENCY`
- Notes:
  - Admin seed uses env values; if not set it defaults to `admin` / `admin@2003`.
  - Keep secrets out of version control.

**Ports and URLs**
- Local dev (Docker): maps host `9005` → container `80`.
- Open in browser:
  - Storefront: `http://localhost:9005/`
  - Admin: `http://localhost:9005/admin/login`

**Run Locally With Docker (recommended)**
- Ensure MySQL is running on your host. Default expected connection:
  - Host: `host.docker.internal` (pre-configured in `docker-compose.yml`)
  - Port: `4987` (change if your MySQL runs on another port)
  - Name: `khqr_store` | User: `root` | Pass: `ikhode` (adjust to your setup)
- Start app:
  - `docker compose up -d --build`
- Run database migrations inside the container:
  - `docker compose exec app vendor/bin/phinx migrate -e development`
- Re-run after schema changes to apply new migrations.

**Run Locally Without Docker**
- Install dependencies: `composer install`
- Configure `.env` for your local MySQL (often `DB_HOST=127.0.0.1`, `DB_PORT=3306`).
- Run dev server: `php -S localhost:9005 -t public`
- Run migrations: `vendor/bin/phinx migrate -e development`

**Database Setup (cPanel / Shared Hosting)**
- Create a new MySQL database and user in cPanel. Note the host (often `localhost`) and port (often `3306`).
- Import schema/data using phpMyAdmin:
  - Open phpMyAdmin → select your database → Import → choose `database/khqr_store.sql` → Go.
- Configure environment on the server:
  - Edit `.env` with your cPanel DB credentials (`DB_HOST=localhost`, `DB_PORT=3306`, `DB_NAME`, `DB_USER`, `DB_PASS`).
  - Set admin defaults if desired (`ADMIN_DEFAULT_USERNAME`, `ADMIN_DEFAULT_PASSWORD`).
  - Set Bakong/KHQR variables.
- Document root setup:
  - Point your domain/subdomain Document Root to the `public/` folder of this project.
  - If you can’t change Document Root, move the contents of `public/` into your hosting’s `public_html/` and ensure `vendor/autoload.php` paths still resolve (Composer install required on the server).
- Optional (SSH-enabled hosts): run migrations instead of importing SQL
  - SSH into the server at the project root.
  - Run `composer install`.
  - Run `vendor/bin/phinx migrate -e development`.

**Phinx: Migrations 101**
- Config: `phinx.php` uses env vars. Default development DB port is `4987` unless you override via `.env`.
- Apply all migrations: `vendor/bin/phinx migrate -e development`
- Roll back last batch: `vendor/bin/phinx rollback -e development -t -1`
- Reset all: `vendor/bin/phinx rollback -e development -t 0`
- Admin seed: included as a migration (`migrations/*seed_admin*.php`), reads credentials from env.

**Troubleshooting**
- Can’t connect to DB (Docker): ensure your host’s MySQL is reachable and matches `DB_*` values. Update `docker-compose.yml` env if your port/user/pass differ.
- 404s on pretty URLs: ensure Apache Rewrite is enabled (Dockerfile already does) and your host points to `public/`.
- Blank page/errors in production: set `APP_ENV=local` temporarily to see errors (do not leave it on in production).

**Data Model (at a glance)**
- Inventory: `products` and `license_keys`.
- Orders: `orders` and `order_items` (pending → paid).
- Delivery proof uploads supported per item (admin UI), stored as configured.

—

Repo: https://github.com/MyKhode/php-composer-khqr
