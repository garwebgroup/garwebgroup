# Information System Backend Library (PHP + MySQL)

A single, self-contained PHP library exposing:
1. Login & Registration API (assignment item #7)
2. RBAC-protected CRUD API for your information system tables (item #8)

## 1. Folder structure

```
backend/
├── config/
│   ├── config.php       # DB credentials, session & security settings
│   └── Database.php     # PDO singleton connection
├── core/
│   ├── Security.php      # sessions, CSRF, sanitization, password hashing
│   ├── RBAC.php           # role/permission matrix + enforcement
│   ├── Auth.php            # register/login/logout/currentUser
│   ├── CrudService.php      # whitelisted generic CRUD engine
│   └── Response.php          # JSON response helper
├── api/
│   └── index.php               # single front controller / router (the only public entry point)
└── sql/
    └── schema.sql                # run once to create the database + tables
```

## 2. Setup

1. Create the database: `mysql -u root -p < sql/schema.sql`
2. Create a dedicated MySQL user for the app (don't use root):
   ```sql
   CREATE USER 'app_user'@'localhost' IDENTIFIED BY 'a-strong-password';
   GRANT SELECT, INSERT, UPDATE, DELETE ON company_info_system.* TO 'app_user'@'localhost';
   ```
3. Edit `config/config.php` (or set environment variables) with your DB host/user/password.
4. Generate an admin account's password hash and insert it manually (see the commented
   INSERT at the bottom of `schema.sql`):
   ```
   php -r "echo password_hash('ChangeMe123!', PASSWORD_BCRYPT), PHP_EOL;"
   ```
5. Point your web server (Apache/Nginx/PHP built-in server) at the project so that
   `api/index.php` is reachable, e.g. `php -S localhost:8000` from the project root.

## 3. Roles (RBAC)

| Role  | Can do |
|-------|--------|
| admin | Full access to all tables, including managing user accounts |
| staff | Manage `employees` and `contact_messages`, read `company_profile` |
| user  | Read-only on `employees`/`company_profile`, can submit contact messages |

New self-registrations always get `role = 'user'`. Promote someone to `staff`/`admin`
by having an existing admin update their row via the `users` CRUD endpoint (or directly
in the database while building the project).

## 4. Calling the API from your frontend JavaScript

All requests go through `api/index.php?action=...`. Always fetch a CSRF token first
(you can do this once on page load and reuse it) and send it back on every
POST/PUT/DELETE (except login/register).

```javascript
// Get a CSRF token once per page load
const { csrf_token } = await fetch('/api/index.php?action=csrf', { credentials: 'include' })
  .then(r => r.json());

// --- Register ---
await fetch('/api/index.php?action=register', {
  method: 'POST',
  credentials: 'include',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    username: 'jdoe', email: 'jdoe@example.com',
    password: 'Secret123', full_name: 'John Doe'
  })
}).then(r => r.json());

// --- Login ---
await fetch('/api/index.php?action=login', {
  method: 'POST',
  credentials: 'include',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ identifier: 'jdoe', password: 'Secret123' })
}).then(r => r.json());

// --- Retrieve (list) employees ---
const list = await fetch('/api/index.php?action=crud&table=employees', {
  credentials: 'include'
}).then(r => r.json());
// list.records -> array of rows, render into a <table>

// --- Add record ---
await fetch('/api/index.php?action=crud&table=employees', {
  method: 'POST',
  credentials: 'include',
  headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf_token },
  body: JSON.stringify({ full_name: 'Ama Owusu', position: 'Developer', department: 'IT', email: 'ama@x.com', phone: '0551234567' })
}).then(r => r.json());

// --- Update record (id=3) ---
await fetch('/api/index.php?action=crud&table=employees&id=3', {
  method: 'PUT',
  credentials: 'include',
  headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf_token },
  body: JSON.stringify({ position: 'Senior Developer' })
}).then(r => r.json());

// --- Delete record (id=3) ---
await fetch('/api/index.php?action=crud&table=employees&id=3', {
  method: 'DELETE',
  credentials: 'include',
  headers: { 'X-CSRF-Token': csrf_token }
}).then(r => r.json());
```

`credentials: 'include'` is required so the session cookie (which carries your
login + role) is sent with each request.

## 5a. Error response shape

Every error response is a JSON object shaped like this:

```json
{
  "success": false,
  "error": "Something went wrong while processing your request. Please try again.",
  "request_id": "a1b2c3d4",
  "debug": {
    "type": "PDOException",
    "message": "SQLSTATE[HY000] [1049] Unknown database 'company_info_system'",
    "file": "/path/to/backend/config/Database.php",
    "line": 31
  }
}
```

- `error` — a generic, safe message. Always present. Show this to end users.
- `request_id` — a short id, always present, also written to the server's PHP
  error log next to the full details. Useful for matching a bug report to a
  log line without exposing internals to the user.
- `debug` — full exception type/message/file/line. **Only ever included when
  `DEBUG_API` is on** (see below) — it is never sent when `APP_ENV=production`.

Simple validation-style errors (e.g. "Missing id", "Unknown table") don't carry
a `debug` block — that's reserved for unexpected exceptions (DB failures, bugs), not expected input errors.

## 5. Security features included

- Prepared statements everywhere (PDO, emulation disabled) — no SQL injection surface.
- Table/column names never come from client input — checked against a fixed
  server-side whitelist in `CrudService::SCHEMA`.
- Passwords hashed with Argon2id (or bcrypt fallback), never stored/returned in plaintext.
- Session cookies: `HttpOnly`, `SameSite=Strict`, `Secure` when served over HTTPS.
- CSRF tokens required on every state-changing request except login/register.
- Login lockout after `MAX_LOGIN_ATTEMPTS` failed tries (brute-force mitigation).
- Generic "invalid username/email or password" message — no user enumeration.
- RBAC enforced at the service layer (`RBAC::requirePermission`), not just in the UI,
  so even a modified frontend request still gets rejected server-side.
- Errors never leak stack traces or DB details to the client (`APP_ENV=production` in config.php).

## 6. What this does NOT include

This library is the **backend only** for items #7 and #8. You still need to build:
- The static pages for items #1–#6 (home, company info, contact, popups, scrolling
  text, image swap) in plain HTML/CSS/JS.
- The login/registration HTML forms and the "manage records" HTML page that call
  the endpoints above with `fetch()`.

If you'd like, I can also build those frontend pages (index.html, login.html, a
records-management page with Add/Retrieve/Update/Delete buttons and the three
JavaScript popups) that wire directly into this API.
