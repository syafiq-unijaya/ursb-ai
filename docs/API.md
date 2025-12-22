# API & Sanctum setup

This project exposes an API under `/api/v1/*` for external clients. The endpoints are protected using Laravel Sanctum personal access tokens. A small CORS middleware (`AllowCors`) is included and applied to `/api/*` routes to allow external origins.

## What I added

- `routes/api.php` — API routes (login, logout, cars CRUD) protected by `auth:sanctum`.
- `app/Http/Controllers/Api/AuthController.php` — login (issue token) and logout (revoke token).
- `app/Http/Controllers/Api/CarController.php` — JSON endpoints for cars (index, show, store, update, destroy).
- `app/Http/Middleware/AllowCors.php` — small CORS middleware applied to API routes.
- `app/Models/User.php` — added the `HasApiTokens` trait to enable Sanctum tokens.

## Setup steps (required)

1. Install Sanctum:

   composer require laravel/sanctum

2. Publish and run migrations (adds `personal_access_tokens` table):

   php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
   php artisan migrate

3. (Optional but recommended) Configure CORS in `.env`:

   CORS_ALLOWED_ORIGIN="*"    # or set the specific origin(s) you want to allow

4. Clear cache/config if needed:

   php artisan config:clear

## Example usage

Get a token:

curl -X POST "http://your-app.test/api/login" -H "Content-Type: application/json" -d '{"email": "user@example.com", "password": "secret", "device_name": "my-client"}'

Response: {"token":"..."}

Use token to call API:

curl -H "Authorization: Bearer <token>" "http://your-app.test/api/v1/cars"

Logout (revoke current token):

curl -X POST -H "Authorization: Bearer <token>" "http://your-app.test/api/v1/logout"

## Notes

- In production, do not set `CORS_ALLOWED_ORIGIN` to `*` — restrict it to trusted domains.
- If you'd rather use cookie-based SPA auth, follow Sanctum's SPA setup (session/cookie) rather than tokens.
- If `routes/api.php` isn't being loaded in your app, the `routes/web.php` file includes it already.

