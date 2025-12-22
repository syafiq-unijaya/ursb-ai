# Copilot / AI Agent Instructions for ursb-ai 🚀

Goal: Help an AI coding agent be productive immediately by describing the app’s architecture, conventions, build/test/dev flows, and concrete examples to reference.

## Big picture
- This is a Laravel 12 app using Inertia + Vue 3 (TS) for the frontend and Fortify for auth. See `composer.json`, `package.json` and `vite.config.ts`.
- Frontend assets are built with Vite and resolved via Inertia (`resources/js/app.ts` uses `resolvePageComponent('./pages/${name}.vue')`).
- SSR is supported (see `resources/js/ssr.ts` and `npm run build:ssr`). `dev:ssr` uses `php artisan pail` and `php artisan inertia:start-ssr`.
- Routing: Laravel server routes live in `routes/*.php` (e.g., `routes/web.php`, `routes/settings.php`). Many pages are rendered via `Inertia::render('...')` so the server-side shapes data for client pages.

## Key files to inspect (examples)
- Auth & views: `app/Providers/FortifyServiceProvider.php` (Fortify::loginView -> Inertia::render('auth/Login')).
- Fortify actions: `app/Actions/Fortify/*` (user creation, reset password).
- Frontend pages: `resources/js/pages/*` and shared UI primitives in `resources/js/components/ui/*`.
- Composables: `resources/js/composables/*` (e.g., `useAppearance.ts`, `useTwoFactorAuth.ts`).
- Tests: `tests/Feature/*` uses Pest and Inertia testing helpers (e.g., `AssertableInertia`).

## Developer workflows (commands you should run)
- Setup (fresh clone):
  - composer install
  - copy `.env.example` → `.env` and run `php artisan key:generate`
  - php artisan migrate
  - npm install && npm run build
  - (Or simply run `composer run setup` which automates the above)

- Local development (hot reload):
  - `composer run dev` (this runs `php artisan serve`, `php artisan queue:listen` and `npm run dev` concurrently)
  - For SSR dev: `composer run dev:ssr` (starts server, queue, pail and SSR worker)

- Running tests:
  - `composer run test` or `./vendor/bin/pest` (CI runs `./vendor/bin/pest` after `npm run build`).
  - Tests use sqlite in-memory (see `phpunit.xml` envs). Mail driver is `array` and queue is `sync` for predictable tests.

- Formatting & linting:
  - PHP: `vendor/bin/pint` (used in CI)
  - JS/TS: `npm run format`, `npm run lint` (CI runs both)

## Project-specific patterns & conventions
- Inertia first: Controllers/ServiceProviders commonly return Inertia pages and pass small props (see `routes/web.php` and `FortifyServiceProvider`). Prefer adding props via server-side code when the UI needs them.
- Pages map 1:1 to `resources/js/pages/*`; `resources/js/app.ts` resolves them automatically.
- UI primitives are centralized under `resources/js/components/ui/*`. Reuse these components (buttons, dialogs, sidebar primitives) rather than making new variants.
- Use composables for page behavior, e.g., `useAppearance.ts` controls theme initialization and is invoked in `app.ts`.
- Fortify customization is done by binding actions and views in `app/Providers/FortifyServiceProvider.php` (no views in resources/views for auth — Inertia handles them).

## Tests & assertions
- Tests use Pest with `RefreshDatabase` in Feature tests (`tests/Pest.php` registers helpers).
- Inertia tests use `Inertia	estingn (Assert $page) => $page->has(...)->where(...)` patterns. Look at `tests/Feature/*` for examples.
- If adding tests that rely on email/queues, remember CI uses `MAIL_MAILER=array` and `QUEUE_CONNECTION=sync` (adjust tests accordingly or swap env locally).

## Integration points & external services
- Authentication: Laravel Fortify (configured in `FortifyServiceProvider`).
- SSR/Logging: `laravel/pail` is used for server-side SSR logs and background tailing in `dev:ssr`.
- Frontend tooling: Vite + Tailwind + `@laravel/vite-plugin-wayfinder` (wayfinder helps forms/routes lookups in the frontend).

## Small but important details
- Composer scripts: `setup`, `dev`, `dev:ssr`, `test` are helpful shortcuts — prefer them to manual sequences.
- CI details: `.github/workflows` runs Pint, Prettier, ESLint; tests workflow runs `npm run build` then Pest.
- TypeScript: types are in `resources/js/types/*` and the project uses `type: "module"` in package.json — keep imports compatible with ESM/TSC.

## Example tasks you can do right away
- Add an Inertia prop to the dashboard: update the `dashboard` route in `routes/web.php` to pass a prop via `Inertia::render('Dashboard', ['foo' => 'bar'])` and update `resources/js/pages/Dashboard.vue` to consume it.
- Fix an Inertia test by mirroring the exact prop names used by the route's render call (see `tests/Feature/*` for patterns).

---
If anything above is unclear or you want more examples (e.g., a short checklist for adding new pages, or a template for new Inertia tests), say which section to expand and I’ll iterate. ✅
