# Developer.md

## Developer Guide

This document explains the internal design, development workflow, extension points, and security-related implementation details of the **SASD GmbH PHP MySQL Health Service**.

The service is intentionally small. It is not a general-purpose framework and not a full diagnostics portal. Its goal is to provide a focused, low-noise, security-conscious health API for MySQL-backed environments.

---

## 1. Service Purpose

The service currently exposes three endpoints:

- `GET /api/health`
- `GET /api/health/time`
- `GET /api/phpinfo`

The two health endpoints are intended for automated checks and operational monitoring. The `phpinfo()` endpoint exists only as an optional administrative aid and is disabled by default.

The core design rule is simple:

**Return only what is operationally necessary and avoid leaking internal details.**

---

## 2. Design Goals

The implementation follows a few deliberate design goals:

### Minimalism
The codebase is intentionally compact. The project avoids unnecessary framework dependencies, service containers, configuration layers, or complex abstractions.

### Safe Defaults
The service avoids exposing stack traces, SQL errors, connection strings, usernames, or file paths. Errors are logged internally and reduced to generic client-facing responses.

### Readability
The code is structured in small files with simple responsibilities so that it can be understood and maintained quickly.

### Easy Deployment
The service can run with Composer, but it also contains a small fallback loading mechanism so the application can still start in very simple deployment situations.

---

## 3. Current Project Structure

```text
public/
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
.env.example
composer.json
README.md
LICENSE
```

### Directory Responsibilities

#### `public/`
Contains the web entry point and Apache rewrite rules. The public web server document root should point here.

#### `src/`
Contains the application logic.

#### `src/Controller/`
Contains request handlers for the currently supported endpoints.

#### `src/Infrastructure/Database/`
Contains database-specific connection logic.

#### `src/Support/`
Contains small low-level helpers such as environment loading and JSON response output.

---

## 4. Request Flow

The request flow is intentionally straightforward.

### Step 1: Front Controller
The web server routes incoming requests to `public/index.php`.

### Step 2: Environment Loading
`public/index.php` makes sure the environment loader is available, then either loads Composer's autoloader or falls back to manually requiring the essential classes.

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

Routing currently lives in `src/Bootstrap.php`.

Supported routes:

- `GET /api/health`
- `GET /api/health/time`
- `GET /api/phpinfo`

Anything else returns:

```json
{
  "status": "error",
  "message": "Not Found"
}
```

### Base Directory Handling
The bootstrap contains a small path normalization step that removes an optional script base directory. This is useful when the application is deployed in a subdirectory instead of directly at the web root.

That means the service can often work both here:

- `https://example.com/api/health`
- `https://example.com/health-service/api/health`

provided the server is configured correctly.

---

## 6. Configuration Model

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

### Important Notes About `Env`

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

This is acceptable for the current scope, but developers should know that it is a deliberately lightweight loader, not a full-featured dotenv implementation.

---

## 7. Database Layer

Database access is currently handled by `DatabaseConnectionFactory`.

### Current State
- only `mysql` is supported
- the connection uses PDO
- `PDO::ATTR_ERRMODE` is set to `PDO::ERRMODE_EXCEPTION`
- emulated prepares are disabled
- default fetch mode is associative arrays

### MySQL Query Behavior
The health endpoint uses:

```sql
SELECT 1
```

The time endpoint uses:

```sql
SELECT DATE_FORMAT(NOW(), '%d.%m.%Y:%H:%i') AS db_time
```

### Driver Restriction
At the moment, any driver other than `mysql` causes a runtime exception.

This is intentional. It keeps the implementation explicit and avoids pretending that multiple databases are supported when only one has been tested.

---

## 8. Controllers

### `HealthController`
Responsible for the two JSON-based database endpoints.

#### `status()`
- creates a PDO connection
- executes `SELECT 1`
- returns a minimal success response
- logs failures internally
- returns HTTP 503 on failure

#### `time()`
- creates a PDO connection
- queries the current database time
- returns it as `db_time`
- logs failures internally
- returns HTTP 503 on failure

### `PhpInfoController`
Responsible for the optional `phpinfo()` endpoint.

#### Behavior
- checks whether `APP_PHPINFO_ENABLED` is true
- reads the expected token from `APP_PHPINFO_TOKEN`
- accepts the provided token from either:
  - `X-Health-Token` header
  - `token` query parameter
- returns `404 Not Found` if disabled
- returns `403 Forbidden` if the token is wrong
- calls `phpinfo()` only if both checks succeed

### Why 404 When Disabled?
Returning `404` instead of a descriptive message makes the endpoint less obvious to casual probing.

---

## 9. Response Strategy

### JSON Endpoints
JSON responses are intentionally minimal.

Success example:

```json
{
  "status": "ok",
  "database": "ok"
}
```

Time example:

```json
{
  "status": "ok",
  "database": "ok",
  "db_time": "24.03.2026:16:42"
}
```

Failure example:

```json
{
  "status": "error"
}
```

### Error Disclosure Policy
The client should never receive:
- SQL exceptions
- DSNs
- stack traces
- file paths
- usernames
- raw connection errors

This policy is one of the most important rules in the project.

---

## 10. Local Development

### Requirements
- PHP 8.1+
- Composer
- MySQL or MariaDB
- `pdo_mysql`

### Setup

```bash
composer install
cp .env.example .env
```

Adjust `.env` for your local database.

### Run Locally

```bash
php -S 127.0.0.1:8080 -t public public/index.php
```

### Quick Tests

```bash
curl http://127.0.0.1:8080/api/health
curl http://127.0.0.1:8080/api/health/time
curl -H "X-Health-Token: your-token" http://127.0.0.1:8080/api/phpinfo
```

---

## 11. Composer and Fallback Loading

The front controller first tries to load Composer's autoloader:

```php
$autoload = dirname(__DIR__) . '/vendor/autoload.php';
```

If that file does not exist, the application falls back to explicitly requiring the core source files.

### Why This Exists
This makes the service more robust in small or improvised environments and keeps the code runnable even when the autoloader is unavailable.

### Limitation
The fallback is manual. If new source files are added later and Composer is not used, the fallback list must also be updated.

Developers should remember this whenever they add classes.

---

## 12. Security Considerations for Developers

### Keep the Service Quiet
Avoid adding debug-heavy endpoints, internal dumps, or diagnostic details to public responses.

### Avoid Credential Exposure
Do not log connection strings, raw passwords, or sensitive request data.

### Treat `phpinfo()` as Dangerous
`phpinfo()` can reveal loaded modules, paths, configuration flags, and environment details. It should remain disabled by default and protected by a strong token when enabled.

### Prefer Principle of Least Privilege
The database user should have only the minimum permissions needed for connectivity testing.

### Avoid Feature Drift
This service is a health endpoint, not an admin panel, not a schema browser, and not a monitoring dashboard.

---

## 13. Logging Philosophy

The code currently uses `error_log()` for internal failure reporting.

This is sufficient for the current scope, but developers should understand the intended behavior:

- detailed enough for administrators to investigate failures
- not detailed enough to expose secrets by accident
- small and dependency-light

### Possible Future Improvement
If the project grows, a structured logger abstraction could be introduced. For now, plain `error_log()` keeps the footprint small.

---

## 14. How to Extend the Service

### Add a New Endpoint
To add an endpoint cleanly:

1. add a new controller method or a new controller class
2. register the route in `Bootstrap.php`
3. keep the response minimal
4. make sure failures do not leak internals
5. update `README.md`, `ADMIN.md`, and this file

### Add a New Database Driver
To add PostgreSQL or Oracle later:

1. extend `DatabaseConnectionFactory`
2. build a driver-specific DSN branch
3. adjust driver-specific health/time SQL
4. document the new `.env` variables
5. test each driver separately

Do not reuse MySQL-specific queries for other drivers without explicit adaptation.

### Add Authentication for More Admin Endpoints
If the service gains more sensitive endpoints in the future, token handling should likely be extracted into a dedicated helper or middleware-like structure instead of being duplicated in multiple controllers.

---

## 15. Suggested Refactoring Paths

The current structure is intentionally small and acceptable for version 1. However, if the service grows, these are sensible next steps:

### Extract a Router
Routing is currently simple enough to stay in `Bootstrap`. If the number of routes grows, a dedicated router class would improve clarity.

### Add a Config Wrapper
`Env` is sufficient now, but a higher-level config class could centralize validation and defaults.

### Introduce Services
If business logic becomes more complex, controllers should stay thin and delegate to dedicated service classes.

### Add Tests
The project would benefit from automated tests once the code starts evolving more actively.

---

## 16. Testing Guidance

There are currently no automated tests in the minimal version.

### Recommended First Tests
If you add a test suite, start with:

- route resolution tests
- environment loader tests
- token validation behavior tests
- 404 and 403 behavior tests
- database failure handling tests

### Integration Testing
The health endpoints are best validated with real integration tests against a disposable MySQL instance.

### Manual Testing Still Matters
Because the service is tiny and infrastructure-focused, manual endpoint checks with `curl` are still valuable even after automated tests are introduced.

---

## 17. Common Developer Pitfalls

### Forgetting the Fallback Loader
If you add new classes but forget to update the fallback `require_once` list in `public/index.php`, the app may work with Composer but fail without it.

### Making Error Messages Too Verbose
It is easy to accidentally leak internal information while debugging. Keep public responses minimal.

### Growing the Scope Too Fast
The service should remain focused. Avoid turning it into a generic diagnostics API.

### Breaking Subdirectory Deployment
Be careful when changing path handling in `Bootstrap.php`. The current normalization helps when the service is deployed below a base path.

### Assuming `.env` Is a Full Dotenv System
It is not. Keep configuration syntax simple unless the loader is intentionally upgraded.

---

## 18. Coding Style Expectations

For future contributions, keep the following style principles:

- prefer small classes with clear responsibilities
- use strict types
- keep method names explicit and predictable
- avoid clever abstractions without strong value
- comment where behavior is non-obvious
- preserve the security-first response policy
- keep the public API stable unless there is a strong reason to change it

---

## 19. Recommended Contribution Workflow

A practical workflow for further development:

1. create a branch for the change
2. keep the scope small
3. test the affected endpoints manually
4. review error behavior carefully
5. update documentation when behavior changes
6. commit with a clear message

Example commit messages:

```text
feat: add PostgreSQL driver support
fix: harden phpinfo token validation
docs: update developer and admin documentation
refactor: extract route handling from bootstrap
```

---

## 20. Final Development Principle

This service should remain:

- small
- predictable
- easy to audit
- easy to deploy
- hard to misuse

Whenever you change the code, ask this question:

**Does this change keep the service minimal, secure, and operationally useful without exposing more than necessary?**

If the answer is no, the change probably does not belong in this project.
