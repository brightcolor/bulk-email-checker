# Bulk Email Checker

A SaaS-ready bulk email verification platform. Supports multiple users, multiple tenants (workspaces), teams within tenants, role-based access control, CSV/TXT uploads, async queue-based processing, and a REST API.

**Tech stack:** PHP 8.3 / Laravel 13, SQLite (dev) / MySQL (prod), AdminLTE 4 + Bootstrap 5 (UI), Laravel Queue (async jobs).

---

## Features

- **Multitenancy** — Users can belong to multiple workspaces. Every data record is strictly scoped to one tenant.
- **Teams** — Workspaces contain teams; jobs can be assigned to teams.
- **Roles** — Owner, Admin, Member, Viewer with server-side enforcement.
- **Bulk Upload** — CSV/TXT upload, deduplication, async processing via queue.
- **Email Verification Engine** — Syntax, DNS/MX, disposable detection, role-account detection, typo suggestions, optional SMTP checks, catch-all detection.
- **API Keys** — Per-tenant API keys (SHA-256 hashed) for REST API access.
- **Audit Log** — All important actions logged per tenant.
- **AdminLTE 4 UI** — Sidebar navigation, Bootstrap tables, progress bars, badges, modals.
- **No Billing/Credits** — No payment system.

---

## Setup

### Requirements

- PHP 8.3+ with extensions: `pdo_sqlite` (or `pdo_mysql`), `openssl`, `mbstring`, `curl`, `fileinfo`
- Composer 2+
- Node.js 20+, npm 10+

### Install

```bash
git clone <repo>
cd bulk-checker
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
npm install
npm run build
```

### Run (development)

```bash
# Start web server
php artisan serve

# Start queue worker (separate terminal)
php artisan queue:work --tries=3

# Or use the composer dev script (requires concurrently):
composer dev
```

The app will be at `http://localhost:8000`. Register a new account — the first user becomes an Owner of a new workspace.

---

## ENV Variables

| Key | Default | Description |
|-----|---------|-------------|
| `APP_NAME` | `Bulk Email Checker` | Application name |
| `APP_URL` | `http://localhost:8000` | Full app URL |
| `DB_CONNECTION` | `sqlite` | Database driver (`sqlite`, `mysql`) |
| `DB_DATABASE` | _(sqlite file)_ | Database name / file path |
| `QUEUE_CONNECTION` | `database` | Queue driver (`database`, `redis`, `sync`) |
| `MAIL_MAILER` | `log` | Mail driver — use `log` for dev |
| `VERIFIER_SMTP_ENABLED` | `false` | Enable SMTP mailbox checks (slow!) |
| `VERIFIER_CATCH_ALL_ENABLED` | `true` | Enable catch-all detection |
| `VERIFIER_SMTP_TIMEOUT` | `5` | SMTP connection timeout (seconds) |
| `VERIFIER_MAX_UPLOAD_SIZE_MB` | `10` | Max upload file size |
| `VERIFIER_MAX_EMAILS_PER_JOB` | `50000` | Max emails processed per job |

---

## Running Migrations

```bash
php artisan migrate
# Fresh start (destroys all data):
php artisan migrate:fresh
```

---

## Queue Worker

Bulk email checks run asynchronously. Start the worker:

```bash
php artisan queue:work --tries=3 --timeout=30
# Or with more detail:
php artisan queue:work --tries=3 --timeout=30 --verbose
```

For production, use Supervisor or a process manager to keep the worker running.

---

## Asset Build (AdminLTE 4 / Bootstrap 5)

Assets are managed via Vite. AdminLTE 4, Bootstrap 5, FontAwesome, and OverlayScrollbars are installed via npm as local packages (no CDN).

```bash
npm install      # Install packages
npm run build    # Production build
npm run dev      # Dev server with HMR
```

The entry points are:
- `resources/css/app.css` — imports AdminLTE, Bootstrap, FontAwesome
- `resources/js/app.js` — imports Bootstrap JS + AdminLTE JS

---

## API Usage

All API endpoints require a tenant-scoped API key. Create one in the UI under **API Keys**.

### Authentication

```http
Authorization: Bearer bec_your_api_key
# or
X-Api-Key: bec_your_api_key
```

### Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `POST` | `/api/v1/email/check` | Check a single email address |
| `GET` | `/api/v1/bulk-jobs` | List bulk jobs for tenant |
| `POST` | `/api/v1/bulk-jobs` | Create a new bulk job (file upload) |
| `GET` | `/api/v1/bulk-jobs/{id}` | Get job status |
| `GET` | `/api/v1/bulk-jobs/{id}/results` | Get paginated results |
| `GET` | `/api/v1/bulk-jobs/{id}/export` | Download results as CSV |

### Single Email Check

```bash
curl -X POST http://localhost:8000/api/v1/email/check \
  -H "Authorization: Bearer bec_yourkey" \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com"}'
```

Response:
```json
{
  "data": {
    "email": "test@example.com",
    "normalized_email": "test@example.com",
    "status": "risky",
    "reason": "",
    "domain": "example.com",
    "mx_valid": true,
    "is_disposable": false,
    "is_role_account": false,
    "suggested_correction": null,
    ...
  },
  "disclaimer": "Email verification results are not guaranteed to be 100% accurate."
}
```

---

## Role Model

| Role | Permissions |
|------|-------------|
| **Owner** | Full access. Manage workspace, members (incl. other owners), API keys, settings, jobs, audit log. |
| **Admin** | Manage members (not owners), teams, jobs, API keys, limited settings. |
| **Member** | Create and view jobs, export results. Cannot manage members or API keys. |
| **Viewer** | View jobs and results only. No creating, no management, export only if allowed. |

Rules:
- An Owner cannot remove themselves if they are the **last owner**.
- Admins cannot remove or demote Owners.
- All role checks are enforced server-side via Laravel Policies.

---

## Tenant Concept

- A **Tenant** (workspace) is the top-level isolation boundary. Every piece of data belongs to exactly one tenant.
- A **User** can be a member of multiple tenants simultaneously with different roles.
- After login, if a user belongs to multiple tenants, they are asked to select one. The active tenant is stored in the session.
- The `SetActiveTenant` middleware validates the session tenant on every protected request. It is impossible to access another tenant's data by manipulating the session — membership is always re-verified server-side.

---

## Email Verifier — What It Does

The verifier runs these checks in order:

1. **Syntax** — RFC-pragmatic validation via `filter_var` + custom rules.
2. **Typo Detection** — Checks against a list of known typos and uses Levenshtein distance against 20+ common domains. Stores suggestions but never modifies the address.
3. **Domain / MX** — DNS lookup for MX records. Falls back to A/AAAA. No MX = `mx_error`.
4. **Disposable** — Compares domain against a built-in list of disposable providers. Loads from `resources/data/disposable-domains.txt` if present.
5. **Role Account** — Recognises `info@`, `admin@`, `noreply@`, etc. as `role_account` / risky.
6. **SMTP** _(optional, off by default)_ — RCPT TO check without sending mail. Handles greylisting/tarpitting conservatively.
7. **Catch-All Detection** _(optional, requires SMTP)_ — Tests a random address on the domain. If accepted, marks the result as `catch_all`.

### Status Values

| Status | Meaning |
|--------|---------|
| `valid` | Syntax OK, MX found, SMTP accepted (if enabled) |
| `invalid` | SMTP rejected with 5xx |
| `risky` | Domain exists, no SMTP confirmation |
| `unknown` | Ambiguous result (greylisting, timeout) |
| `disposable` | Known disposable/temporary email domain |
| `catch_all` | Domain accepts all addresses |
| `role_account` | Generic role address (info@, admin@, …) |
| `syntax_error` | Invalid email syntax |
| `mx_error` | Domain has no MX or A records |
| `smtp_error` | SMTP check could not be completed |

### Important Limitations

Email verification is **never 100% accurate**:
- Large providers (Gmail, Outlook) block or lie about SMTP checks.
- Greylisting temporarily rejects valid addresses.
- Catch-all domains accept everything, making RCPT TO useless.
- Results marked `risky` or `unknown` are **not** confirmed invalid — do not use them to unsubscribe or delete contacts without further verification.

---

## Data Privacy / GDPR Notes

- Upload files are stored in `storage/app/local/uploads/{tenant_id}/`.
- Results are stored in the database. Configure `result_retention_days` in Workspace Settings.
- Manually delete old jobs and their results from the Bulk Jobs list.
- The Audit Log records actor, action, and timestamp — no email list contents are logged.
- No data is sent to third parties except DNS queries to public resolvers and (if SMTP enabled) SMTP RCPT TO checks to target mail servers.

---

## Running Tests

```bash
php artisan test
# Specific suite:
php artisan test --filter TenantIsolationTest
php artisan test --filter RoleAuthorizationTest
php artisan test --filter EmailVerifierTest
php artisan test --filter BulkJobTest
```

Tests use an in-memory SQLite database and sync queue (`QUEUE_CONNECTION=sync`).

---

## Known Limitations

- No billing, credits, or subscription system (by design).
- SMTP checks are disabled by default and can be slow (configurable timeout).
- Large jobs (50,000+ emails) require the queue worker to be running.
- Rate limiting per target domain is prepared in code but not enforced at infrastructure level.
- The disposable domain list is a built-in static list. Replace with a more comprehensive external list for production.
