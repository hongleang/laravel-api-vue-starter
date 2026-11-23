---
name: docker-development
description: "Run, test and debug the Laravel API + Vue starter in its Docker stack (nginx, PHP-FPM, queue, MySQL, a separate MySQL test database, Mailpit and the Vite dev server). Use when asked to start the app in Docker, run the tests against MySQL, check mail, inspect logs, or change docker-compose.yml, backend/Dockerfile or docker/ config."
---

# Docker development

## When to use this skill

Use this when running the starter in Docker, running the backend tests against MySQL, checking emails in Mailpit, or editing `docker-compose.yml`, `backend/Dockerfile`, `backend/docker/php.ini` or `docker/nginx/default.conf`. Run every command from the repository root, where `docker-compose.yml` lives.

The native setup (`composer dev` in `backend/`, `npm run dev` in `frontend/`, SQLite tests) still works and is what CI uses.

## Services and ports

| Service | What it is | Host port |
| --- | --- | --- |
| `frontend` | Vite dev server for the SPA; proxies `/api` and `/sanctum` to nginx | 8080 |
| `nginx` | Serves the Laravel API, passes PHP to `app` | 8080 |
| `app` | PHP 8.5 FPM, `backend/` bind-mounted | none |
| `queue` | `php artisan queue:work` | none |
| `mysql` | App database `starter` (user `starter`, password from `DB_PASSWORD` in the root `.env`), in the `mysql-data` volume | 33060 |
| `mysql-test` | Throwaway test database `starter_test` on tmpfs | 33061 |
| `mailpit` | Catches all mail; web UI | 8025 UI, 1025 SMTP |
| `test` | One-shot `php artisan test` against `mysql-test` (profile `test`) | none |

Open the SPA at http://localhost:8080 (seeded logins: `admin@example.com` and `user@example.com`, password `password`) and Mailpit at http://localhost:8025. Values in `docker-compose.yml` are literal; to change a port or credential, edit it directly (and keep `FRONTEND_URL`/`SANCTUM_STATEFUL_DOMAINS` matching the SPA's port).

## Commands

```bash
cp .env.example .env                              # once: set DB_PASSWORD
docker compose build --pull app                   # build, picking up the newest PHP 8.5 patch
docker compose up -d                              # start everything
docker compose exec app php artisan migrate --seed --force   # first run only
docker compose run --rm test                      # backend suite against MySQL
docker compose run --rm test php artisan test --compact --filter=UserManagementTest
docker compose exec app php artisan tinker
docker compose logs -f app frontend nginx
docker compose down                               # stop; add -v to also delete the MySQL volume
```

## Rules

- **Never point `app` at `mysql-test` or set `APP_ENV=testing` on it.** Environment variables override `phpunit.xml`, so tests run inside `app` would use the dev database and `RefreshDatabase` would wipe it. Run tests only through the `test` service.
- **Do not run `migrate:fresh` against `mysql`** without the user's approval: it holds their Docker dev data.
- Compose sets the database, mail, `APP_URL`, `FRONTEND_URL` and `SANCTUM_STATEFUL_DOMAINS` as environment variables, which win over `backend/.env` inside the containers. Do not edit `backend/.env` to make Docker work, and never print or commit `.env` values.
- `DB_PASSWORD` must be set in the root `.env`, or compose refuses to start. MySQL only applies it when the `mysql-data` volume is first created: after changing it, run `ALTER USER 'starter'@'%' IDENTIFIED BY '…'` as root in `mysql`, or `docker compose down -v` (deletes the Docker data; needs approval).
- `backend/phpunit.xml` stays on SQLite in memory; do not change it to MySQL.

## Gotchas

- **No healthchecks, so nothing waits for MySQL.** On the very first start MySQL initialises for a while and even runs a temporary server, so `migrate` can fail with a connection error. Wait until `docker compose logs mysql` has finished initialising, then run it again. `mysql-test` starts empty every time.
- **Port clashes:** these ports match other local stacks (for example 8080, 33060, 8080). Stop the other stack, or the containers fail to bind.
- **The SPA shows "Could not reach the server":** `nginx` or `app` is down; check `docker compose ps` and `docker compose logs app nginx`. nginx re-resolves `app` through Docker DNS, so recreating `app` needs no nginx restart.
- **File ownership:** the backend image's user is built with uid 501 and gid 20 (the owner's Mac). On other machines, change `UID`/`GID` under `x-backend.build.args` and rebuild, or the containers cannot write to `storage/`.
- **`frontend` runs `npm install` on start**, into the `frontend-node-modules` volume (separate from the host's `node_modules`); the first start takes a minute.
- Tinker and Composer need a writable home; the image sets `HOME=/home/www-data`.
