# EventHub

Laravel backend, a Vue 3 + Vite frontend, and a PostgreSQL database, wired together with Docker Compose.

---

## Stack & Architecture

- **Monorepo layout**
  - `apps/backend` - **Laravel ^9.19** REST API
  - `apps/frontend` - **Vue 3 + Vite + TypeScript + Pinia + Vue Router**
  - `postgres` - via Docker with init script
  - `docker-compose.yml` orchestrates **db + backend + frontend**
- **Auth is session-based (Laravel Sanctum, stateful)**.
  - CSRF cookie fetched from `/sanctum/csrf-cookie`
  - Axios is configured with `withCredentials: true` and sends `X-XSRF-TOKEN`
- **CORS** is enabled with credentials for `http://localhost:5173`/`5174`.

---

## Backend (Laravel)

### Key features
- **Session-based authentication (Sanctum)** with endpoints:
  - `POST /api/login` (rate-limited)
  - `POST /api/register` (rate-limited)
  - `POST /api/logout` *(requires auth)*
  - `GET /api/user` *(requires auth)* - returns the current user
- **Roles & user state**
  - `users.role` is an enum: `admin` | `organizer` | `user`.
  - `users.enabled` boolean flag; middleware **blocks disabled users** from most routes, returning HTTP **423** (see `EnsureUserEnabled`).
  - Helper methods on `User` model: `isAdmin()`, `isOrganizer()`, `isUser()`.
- **Events**
  - Table columns: `title`, `description`, `starts_at`, `location`, `capacity` (nullable = unlimited), `category`, `status` (`draft|published|cancelled`), `price` (nullable), `max_tickets_per_user` (default **5**), `created_by` user FK.
  - **Search/filter** implemented server-side in `EventService::list()` with pagination.
  - **Only `published` and future events are bookable** (`Event::isBookableNow()`).
  - Organizer/Admin can **create/update/delete/publish** their own events (policy: `EventPolicy`), Admin bypasses ownership checks.
- **Bookings**
  - **Guests can book** (no auth required), or authenticated users can book.
  - Booking stores `quantity`, computed `total_price`, and optional `guest_name`/`guest_email` when not logged in.
  - **Concurrency-safe seat handling** with a DB transaction & `FOR UPDATE`–style locking in `BookingService::create()`; there’s a **feature test** (`BookingConcurrencyTest`) that asserts one of two parallel bookings fails with **422**.
  - Per-user maximum enforced via `events.max_tickets_per_user` (default **5**).
  - Authenticated users can list **their own bookings** via `GET /api/bookings`.
- **Admin**
  - `GET /api/admin/users` - list users (filters + pagination).
  - `PATCH /api/admin/users/{id}/enabled` - toggle `enabled`; **cannot disable the last enabled admin** (validated in `AdminUserService`).
- **Error handling & safety**
  - Responses are forced to JSON (`ForceJsonResponse`) and include an `X-Request-Id` header (`RequestId`).
  - Centralized exception handler avoids leaking stack traces in 500s, returns localized messages and includes `requestId` and `timestamp` (see `App\Exceptions\Handler`).

### API routes

**Public**
```
GET    /api/ping
POST   /api/login
POST   /api/register
GET    /api/events
GET    /api/events/{eventId}
POST   /api/events/{eventId}/bookings
```

**Authenticated (`auth:sanctum`)**
```
POST   /api/logout
GET    /api/user

POST   /api/events
PUT    /api/events/{eventId}
DELETE /api/events/{eventId}
PATCH  /api/events/{eventId}/status

GET    /api/bookings

GET    /api/admin/users                (admin)
PATCH  /api/admin/users/{userId}/enabled (admin)
```

### Database & migrations

- PostgreSQL 14+
- Migrations create **users**, **events**, **bookings**.
- Seeders:
  - `DemoUsersSeeder` adds:
    - **Admin** `admin@eventhub.local / Admin123!`
    - **Organizer** `org@eventhub.local / Org123!`
    - **User** `user@eventhub.local / User123!`
  - `EventSeeder`, `BookingSeeder` provide demo data.

---

## Frontend (Vue 3 + Vite + TS)

- **Auth UI** with **Login / Register / Continue as Guest** (see `LoginView.vue`), wired to the session endpoints above.
- **Global HTTP client** (`src/lib/http.ts`) configured with `withCredentials` and CSRF bootstrap via `ensureCsrfCookie()`.
- **Role-guarded routes** (`src/router/index.ts`):
  - Redirects away from auth pages if already logged in.
  - Protects routes by `roles` meta (`'user' | 'organizer' | 'admin'`).
- **Views**
  - `HomeView.vue`: Event list with **search/filter/pagination**, create/edit/publish for **organizer/admin**.
  - `TicketsView.vue`: Authenticated user’s **own bookings** (+ filtering).
  - `UsersView.vue`: **Admin** user list with **enable/disable** toggle.
- **State management** via **Pinia** (`stores/auth.ts`, `stores/ui.ts`).

### Env & runtime
- `VITE_API_BASE_URL` defaults to `http://localhost:8000/api`.
- Axios sends cookies; backend CORS is configured to allow credentials.

---

## Running the project

### Prerequisites
- **Docker** & **Docker Compose**

### Quick start
```bash
# from repo root
cp .env.example .env
docker compose up -d
```

This will:
- Start **Postgres** and create a test DB via `db-init/01-init-test-db.sql`
- Bring up **backend** (installs deps, runs **migrations** and **seeders**, starts on `:8000`)
- Bring up **frontend** (Vite dev server on `:5173`)

**Default URLs**
- API: `http://localhost:8000/api`
- Web: `http://localhost:5173`

### Seed users
| Role      | Email                   | Password  |
|-----------|-------------------------|-----------|
| Admin     | admin@eventhub.local    | Admin123! |
| Organizer | org@eventhub.local      | Org123!   |
| User      | user@eventhub.local     | User123!  |

---

## Known limitations (as coded)
- Payments are **not** implemented; bookings compute `total_price` if `events.price` is set, but there’s no gateway.
- Email verification & password reset flows are not wired into the UI (Laravel scaffolding is present for resets).
- No file uploads/images for events.
- Public booking requires `guest_name` and `guest_email`; there’s no guest booking email confirmation.

---

## Project layout
```
apps/
  backend/    # Laravel API (session auth via Sanctum, role policies, seeders, tests)
  frontend/   # Vue 3 + Vite + TS + Pinia + Vue Router
db-init/
docker-compose.yml
.env.example
```

---

## Development tips
- If you change CORS or ports, update both **backend** `config/cors.php` and **frontend** `.env` (`VITE_API_BASE_URL`).
- The backend attaches `X-Request-Id` to every response, include it when reporting issues.
- Feature & unit tests are present in `apps/backend/tests` (booking concurrency, event policy, admin rules, etc.).

MIT licensed.
