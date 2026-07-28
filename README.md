# RelayHub Backend

RelayHub is a Laravel 13 API backend for a real-time chat app — JWT-authenticated REST API, role-based permissions (Spatie), WebSocket broadcasting (Laravel Reverb), file uploads with per-user storage quotas, and a Filament admin panel. This is the backend the [RELAYHUB_FRONTEND](https://github.com/DhanyaHegdek/RELAYHUB_FRONTEND) React app talks to.

## What it does

### Auth (`AuthController`)
- `POST /api/register` — creates a user, assigns the `user` role, returns a JWT.
- `POST /api/login` — JWT login via email/password.
- `POST /api/logout`, `GET /api/me` — logout and "who am I" (includes the user's role, so the frontend knows whether to show the admin panel).

### Chat (`ChatController`)
- `POST /api/conversations` — start (or fetch existing) 1:1 conversation with another user.
- `GET /api/conversations` — list the logged-in user's conversations, ordered by most recent activity.
- `GET /api/conversations/{id}/messages` — fetch a conversation's messages (with sender + reply-to info).
- `POST /api/conversations/{id}/messages` — send a message; broadcasts a `MessageSent` event over Reverb to the other participant in real time.
- `GET /api/conversations/{id}/messages/search` — search messages within a conversation.
- `POST /api/conversations/{id}/upload` — upload a file into a conversation (enforces a per-user storage quota).
- `GET /api/conversations/{id}/files` — list files shared in a conversation.
- `GET /api/storage-info` — the current user's storage usage/quota.

### Admin (`AdminController`, requires `admin` role)
- `GET /api/admin/users`, `GET /api/admin/users/{id}` — list/view users.
- `POST /api/admin/users` — create a user.
- `DELETE /api/admin/users/{id}` — delete a user.
- `PUT /api/admin/users/{id}/role` — change a user's role.

### Real-time
- `routes/channels.php` defines private channels: a per-user notification channel, and a per-conversation channel that only the two participants can subscribe to.
- `POST /api/broadcasting/auth` authorizes channel subscriptions for the frontend's Laravel Echo client.

### Admin panel
- Filament 5 is installed with `filament-shield` (role/permission management UI) and `filament-spatie-roles-permissions`, giving you a `/admin`-style dashboard for managing users, roles, and permissions visually — check `app/Filament/Resources` for what's registered.

## Tech Stack

- **Framework:** Laravel 13 (PHP 8.3+)
- **Auth:** JWT (`php-open-source-saver/jwt-auth`) on the `api` guard, Laravel Fortify for web-side auth features (2FA, etc.)
- **Roles/permissions:** Spatie Laravel Permission
- **Admin panel:** Filament 5 + Filament Shield
- **Real-time:** Laravel Reverb (WebSocket server, Pusher-protocol compatible)
- **Frontend (server-rendered pages):** Inertia.js + React (for the `/` welcome page and `/dashboard`, separate from the API-driven chat SPA)
- **Testing:** Pest

## Setup

### 1. Clone and install dependencies

```bash
git clone https://github.com/DhanyaHegdek/RELAYHUB.git
cd RELAYHUB
composer install
npm install
```

### 2. Environment setup

```bash
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

By default `.env.example` uses SQLite (`DB_CONNECTION=sqlite`) — create the database file:

```bash
touch database/database.sqlite
```

Or switch `DB_CONNECTION` to `mysql`/`pgsql` and fill in the corresponding `DB_*` variables if you'd rather use a real database server. (Note: one query in `ChatController::searchMessages` uses PostgreSQL's `ILIKE` operator — see **Known Issues** below if you're not on Postgres.)

### 3. Add Reverb / broadcasting variables

`.env.example` doesn't include these, but they're required for real-time chat to work — add to your `.env`:

```
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=relayhub
REVERB_APP_KEY=your_local_key       # must match VITE_REVERB_APP_KEY in the frontend's .env
REVERB_APP_SECRET=your_local_secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
```

### 4. Migrate and seed

```bash
php artisan migrate
php artisan db:seed --class=Database\\Seeders\\RolesAndPermissionsSeeder
```

This creates the `user`/`admin` roles + permissions and a default admin account: **admin@relayhub.com / password**. (See **Known Issues** — this seeder isn't wired into the default `db:seed` command, so it must be run explicitly as shown above.)

### 5. Run everything

You'll need three processes running at once:

```bash
php artisan serve                 # API server
php artisan reverb:start          # WebSocket server (real-time chat)
php artisan queue:listen --tries=1  # queued jobs (broadcasting, etc.)
```

Or use the bundled dev script, which runs the server, queue listener, and Vite together (Vite here is for the Inertia-rendered welcome/dashboard pages, not the separate React frontend repo):

```bash
composer run dev
```

### 6. Connect the frontend

Point the [RELAYHUB_FRONTEND](https://github.com/DhanyaHegdek/RELAYHUB_FRONTEND) app's API base URL (`src/api/axios.js`, `src/echo.js`) at wherever this backend is running, and make sure its `VITE_REVERB_APP_KEY` matches `REVERB_APP_KEY` here.

## Known Issues

- **`AdminController` filename case mismatch:** the file is named `Admincontroller.php`, but the class inside is `AdminController`. PSR-4 autoloading requires the filename to exactly match the class name — this resolves fine on case-insensitive filesystems (macOS/Windows) but **will fail to autoload on Linux** (including most production servers and CI). Rename the file to `AdminController.php` to fix.
- **`RolesAndPermissionsSeeder` isn't called from `DatabaseSeeder`:** running plain `php artisan db:seed` only creates a test user — no roles, no admin account. Run the seeder explicitly (see step 4 above), or add `$this->call(RolesAndPermissionsSeeder::class);` to `DatabaseSeeder::run()`.
- **Postgres-specific SQL with a SQLite default:** `ChatController::searchMessages` uses `ILIKE`, which only exists in PostgreSQL — this will error out on the default SQLite setup (or MySQL). Swap to `LIKE` with `LOWER()` on both sides for cross-database compatibility, or standardize on Postgres.

## Project Structure

```
RELAYHUB/
├── app/
│   ├── Http/Controllers/    # AuthController, ChatController, Admincontroller, ProfileController
│   ├── Models/              # User, Conversation, Message
│   ├── Events/              # MessageSent (broadcast over Reverb)
│   ├── Filament/Resources/  # Admin panel resources
│   ├── Policies/
│   └── Providers/
├── routes/
│   ├── api.php              # REST API (used by the React frontend)
│   ├── web.php              # Inertia welcome/dashboard pages
│   └── channels.php         # Broadcast channel authorization
├── database/
│   ├── migrations/
│   └── seeders/             # DatabaseSeeder, RolesAndPermissionsSeeder
├── config/reverb.php, broadcasting.php, jwt.php, auth.php
└── tests/
```
