# Laravel API + Vue Starter

A starter for apps with a **Laravel 13 JSON API** and a **Vue 3 + TypeScript SPA**: authentication, profile and settings, user management with roles and permissions, and a shadcn-vue component kit with dark mode.

The SPA mirrors Inertia's developer experience without Inertia: `useForm`, `useHttp`, `usePage` and `setLayoutProps` behave like their Inertia counterparts, on top of Vue Router and Pinia.

## What's included

**Backend (`backend/`)**: Laravel 13, PHP 8.4+
- Sanctum SPA authentication with session cookies (no tokens): login, logout, forgot and reset password, email verification
- Profile update and password change
- User CRUD with search, role filter, sorting and pagination
- Roles and permissions with `spatie/laravel-permission`: `admin` has `users.view`, `users.create`, `users.update` and `users.delete`; `user` has none
- API Resources only, Form Requests and Laravel Data for validation, policies for every action, one JSON error format
- PHPUnit feature tests for every endpoint, Pint, Larastan level 7, Laravel Boost with project rules in `backend/.ai/`

**Frontend (`frontend/`)**: Vue 3, TypeScript, Vite, Vue Router, Pinia, Tailwind CSS 4
- shadcn-vue components (reka-ui), app layout with collapsible sidebar, auth layout, data table, pagination, confirm dialog, form fields
- Pages: login, forgot and reset password, verify email, dashboard, profile, preferences (dark mode), users index, create, show and edit
- Inertia-style composables: `useForm`, `useHttp`, `usePage`, `setLayoutProps`, plus `useAppearance`, `useFlashToast` and `useDelayedFlag`
- Route guards for guest, auth, verified and permission routes; loading, empty and error states everywhere
- ESLint (Vue 3 recommended), Prettier and `vue-tsc`

## Requirements

- PHP 8.4+ and Composer 2
- Node 22 and npm
- SQLite (default), or Docker for the MySQL stack

## Setup

```bash
git clone <repo-url> laravel-api-vue-starter
cd laravel-api-vue-starter

# API
cd backend
composer setup          # install, .env, app key, SQLite database, migrate and seed
cd ..

# SPA
cd frontend
cp .env.example .env
npm install
cd ..
```

## Running

In two terminals:

```bash
cd backend && composer dev      # API on http://localhost:8000
cd frontend && npm run dev      # SPA on http://localhost:8080
```

Open http://localhost:8080. The Vite dev server proxies `/api` and `/sanctum` to the API, so the browser only talks to one origin: Sanctum's session cookie is first-party and there is no CORS to configure.

Mail is written to `backend/storage/logs/laravel.log` (`MAIL_MAILER=log`). Reset and verification links in those emails point at the SPA.

### Demo accounts

| Email | Password | Role |
| --- | --- | --- |
| `admin@example.com` | `password` | admin: can manage users |
| `user@example.com` | `password` | user: profile and settings only |

The seeder also creates 20 more users. Reset with `php artisan migrate:fresh --seed` in `backend/` (deletes local data).

## Docker

A MySQL stack with nginx, PHP-FPM, a queue worker, a throwaway test database, Mailpit and the Vite dev server:

```bash
cp .env.example .env                # set DB_PASSWORD
docker compose up -d
docker compose exec app php artisan migrate --seed --force
docker compose run --rm test        # backend tests against MySQL
```

| URL | What |
| --- | --- |
| http://localhost:8080 | SPA |
| http://localhost:8080 | API (nginx) |
| http://localhost:8025 | Mailpit |

See `backend/.ai/skills/docker-development/SKILL.md` for every command and gotcha.

## Commands

| Where | Command | What it does |
| --- | --- | --- |
| `backend/` | `composer test` | PHPUnit suite |
| `backend/` | `composer lint` / `lint:fix` | Check / fix formatting with Pint |
| `backend/` | `composer analyse` | Larastan, level 7 |
| `frontend/` | `npm run lint` | ESLint |
| `frontend/` | `npm run format` | Prettier |
| `frontend/` | `npm run types` | `vue-tsc` type check |
| `frontend/` | `npm run build` | Production build |

CI (`.github/workflows/ci.yml`) runs all of them on every pull request.

## How the SPA talks to the API

- `frontend/src/lib/http.ts` is the only HTTP client. It sends cookies, fetches `/sanctum/csrf-cookie` and the `X-XSRF-TOKEN` header, converts data with files to FormData (spoofing PUT/PATCH like Laravel forms), retries once on a 419, and emits `unauthorized`, `httpException`, `networkError` and `success` events.
- `useForm` keeps fields on the form object for `v-model`, maps a 422 response onto `form.errors` (first message per field), and offers `processing`, `isDirty`, `wasSuccessful`, `recentlySuccessful`, `reset`, `clearErrors`, `setError`, `transform` and `defaults`. Callbacks: `onBefore`, `onStart`, `onSuccess`, `onError`, `onHttpException`, `onNetworkError`, `onCancel`, `onFinish`.
- `main.ts` turns success `message`s into toasts, shows other errors as toasts, and sends a 401 back to the login page.
- API URLs live in `frontend/src/api`, and pages link with named routes; there is no Wayfinder.

## Working with AI agents

Agent instructions live in `backend/.ai/`: the working agreement (`guidelines/`), path-scoped rules (`rules/index.md`) and skills (`skills/`). After cloning, run `php artisan boost:install` in `backend/` to generate `AGENTS.md`, `CLAUDE.md` and the agent skill folders from them (those generated files are gitignored).
