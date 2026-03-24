# DEVELOPER.md

## Developer Guide

This document explains the internal design, development workflow, extension points, and security-related implementation details of the **SASD GmbH PHP MySQL Health Service**.

This edition is specifically aligned with **subfolder deployment** below a shared API host, for example `https://api.sasd.de/health`.

---

## 1. Service Purpose

The service exposes three externally visible routes in production:

- `GET /health`
- `GET /health/time`
- `GET /health/phpinfo`

Internally, after base path normalization, the application routes to:

- `/`
- `/time`
- `/phpinfo`

The two health endpoints are intended for automated checks and operational monitoring. The `phpinfo()` endpoint exists only as an optional administrative aid and is disabled by default.

The core design rule is simple:

**Return only what is operationally necessary and avoid leaking internal details.**

---

## 2. Design Goals

### Minimalism
The codebase is intentionally compact. The project avoids unnecessary framework dependencies, service containers, configuration layers, or complex abstractions.

### Safe Defaults
The service avoids exposing stack traces, SQL errors, connection strings, usernames, or file paths. Errors are logged internally and reduced to generic client-facing responses.

### Readability
The code is structured in small files with simple responsibilities so that it can be understood and maintained quickly.

### Easy Deployment
The service can run with Composer, but it also contains a small fallback loading mechanism so the application can still start in very simple deployment situations.

### Shared Hosting Compatibility
The service is prepared to live in a physical subfolder such as `health/`, while still routing requests as if the service root were `/` internally.

---

## 3. Current Project Structure

```text
index.php
.htaccess
src/
  Bootstrap.php
  Controller/
    HealthController.php
    PhpInfoController.php
  Infrastructure/
    Database/
      DatabaseConnectionFactory.php
  Support/
    Env.php
    JsonResponse.php
docs/
  ADMIN.md
  API_REFERENCE.md
  CHANGELOG.md
  CONTRIBUTING.md
  SECURITY.md
  TESTING.md
.env.example
composer.json
README.md
LICENSE
```

### Directory Responsibilities

#### `index.php`
The front controller and application entry point.

#### `.htaccess`
Apache rewrite and access-control rules for subfolder deployment.

#### `src/`
Contains the application logic.

#### `src/Controller/`
Contains request handlers for the supported endpoints.

#### `src/Infrastructure/Database/`
Contains database-specific connection logic.

#### `src/Support/`
Contains low-level helpers such as environment loading and JSON response output.

#### `docs/`
Contains operator, developer, API, security, and testing documentation.

---

## 4. Request Flow

The request flow is intentionally straightforward.

### Step 1: Front Controller
Apache routes incoming requests inside the `health/` folder to `index.php` through `.htaccess`.

### Step 2: Environment Loading and Class Loading
`index.php` loads Composer's autoloader when it exists. If it does not exist, the script falls back to manual `require_once` statements for the small set of application classes.

### Step 3: Bootstrap
`Bootstrap::run()` loads `.env`, reads the HTTP method and path, normalizes the request path, and routes to the corresponding controller.

### Step 4: Controller Execution
The selected controller performs the relevant operation:

- database connectivity check
- database time query
- optional `phpinfo()` rendering

### Step 5: Response
JSON responses are sent by `JsonResponse`. The `phpinfo()` endpoint returns HTML output directly because that is how `phpinfo()` works.

---

## 5. Routing Details

Routing lives in `src/Bootstrap.php`.

### External Production Routes

- `GET /health`
- `GET /health/time`
- `GET /health/phpinfo`

### Internal Normalized Routes

- `GET /`
- `GET /time`
- `GET /phpinfo`

Anything else returns:

```json
{
  "status": "error",
  "message": "Not Found"
}
```

### Base Directory Handling

The bootstrap removes the service base directory from the request path by looking at `dirname($_SERVER['SCRIPT_NAME'])`.

Examples:

- request URI `/health/time`
- script name `/health/index.php`
- detected base directory `/health`
- normalized route `/time`

This keeps the actual routing logic independent from the deployment folder name.

The same files can therefore run:

- locally at `/`
- in production at `/health`

provided the surrounding server configuration is correct.

---

## 6. Apache and `.htaccess` Notes

This project no longer assumes a dedicated `public/` directory. The service root itself is the web entry point for the specific service folder.

The `.htaccess` file is therefore more important than in a separated public-webroot model.

Main tasks of `.htaccess` in this project:

- disable `MultiViews`
- enable front-controller routing
- preserve direct access only for existing allowed files
- deny direct access to internal code and documentation folders
- deny direct access to selected metadata and secret-related files

This is especially important on shared hosting where the service folder itself is directly reachable.

---

## 7. Configuration Model

Configuration is loaded from `.env` using the custom `Env` helper.

### Supported Variables

```env
APP_ENV=production
APP_DEBUG=false

DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=your_database
DB_USER=your_user
DB_PASS=your_password
DB_CHARSET=utf8mb4

APP_PHPINFO_ENABLED=false
APP_PHPINFO_TOKEN=replace-this-with-a-long-random-token
```

### Notes About `Env`

The environment loader is intentionally simple.

It currently supports:

- plain `KEY=value` pairs
- quoted values
- comment lines starting with `#`

It does **not** currently support:

- nested variable expansion
- multiline values
- advanced escaping rules
- schema validation
- automatic type conversion except for `getBool()`

This is acceptable for the current scope, but developers should know that it is a deliberately lightweight loader, not a full-featured configuration framework.

---

## 8. Controller Behavior

### `HealthController`

#### `status()`
- opens a database connection
- executes `SELECT 1`
- returns JSON success if the operation works
- returns generic `503` JSON on failure
- logs failures internally through `error_log()`

#### `time()`
- opens a database connection
- executes:

```sql
SELECT DATE_FORMAT(NOW(), '%d.%m.%Y:%H:%i') AS db_time
```

- returns the formatted database time
- returns generic `503` JSON on failure
- logs failures internally through `error_log()`

### `PhpInfoController`

#### `show()`
- checks whether `APP_PHPINFO_ENABLED` is true
- extracts the token from `X-Health-Token` or `?token=`
- compares it using `hash_equals()`
- returns `404` when disabled
- returns `403` when authentication fails
- executes `phpinfo()` only when both checks succeed

---

## 9. JSON Response Behavior

`JsonResponse` is intentionally small.

It currently:

- sets the HTTP status code
- sends JSON content type headers
- disables caching
- adds `X-Content-Type-Options: nosniff`
- suppresses the response body for `HEAD` requests

The latter makes the health endpoint more usable for monitoring tools that prefer `HEAD`.

---

## 10. Local Development

A simple local run is enough for most work:

```bash
php -S 127.0.0.1:8080 index.php
```

Then use these local routes:

- `http://127.0.0.1:8080/`
- `http://127.0.0.1:8080/time`
- `http://127.0.0.1:8080/phpinfo`

The subfolder prefix `/health` does not exist in this local mode unless you deliberately emulate the parent host structure.

---

## 11. Manual Test Recommendations

Developers should at least verify the following after changes:

- `/` returns success with valid DB configuration
- `/time` returns a database time string
- wrong DB credentials return generic `503` JSON
- unknown routes return generic `404` JSON
- `phpinfo()` is unreachable when disabled
- `phpinfo()` returns `403` with missing or wrong token
- direct access to `src/` and `.env` is blocked by Apache

See `docs/TESTING.md` for a fuller checklist.

---

## 12. Extending the Service

This service is intentionally small. If you add features, preserve the architectural spirit.

Good additions might include:

- additional narrowly scoped health-related endpoints
- small security improvements
- small operational improvements
- additional tests or validation helpers

Be careful with additions that:

- expose internal information
- turn the service into a broad diagnostics portal
- introduce large dependencies without strong justification
- make responses too verbose

---

## 13. Typical Pitfalls

### Wrong Path Assumptions
Do not hardcode production paths like `/api/health` into the application logic. The internal routes should remain `/`, `/time`, and `/phpinfo`.

### Shared Hosting Confusion
If the subdomain points to the wrong parent folder, the application will not be reached regardless of how correct the PHP code is.

### `MultiViews`
If `Options -MultiViews` is removed, Apache may produce confusing path resolution behavior such as `300 Multiple Choices`.

### Direct File Exposure
Because the service root is web-facing in this deployment model, Apache access rules are part of the security model. Keep `.htaccess` intact.

---

## 14. Final Recommendation

Treat this repository as a compact service package for a single subfolder API service.

Keep the code readable, the routing simple, and the external responses intentionally quiet.
