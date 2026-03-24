# SASD GmbH PHP MySQL Health Service

A minimal, security-focused RESTful PHP health service for MySQL.

This edition is prepared for **subfolder deployment** under a shared API host, for example:

- `https://api.sasd.de/health`
- `https://api.sasd.de/health/time`
- `https://api.sasd.de/health/phpinfo`

The service reads database settings from `.env`, performs a live MySQL connectivity check, can return the current database server time, and can optionally expose a token-protected `phpinfo()` endpoint for diagnostics.

## Features

- `GET /health` — performs a live database connectivity check
- `GET /health/time` — returns the current time from the database server
- `GET /health/phpinfo` — optional `phpinfo()` endpoint, disabled by default and protected by token authentication
- intentionally minimal JSON responses without leaking DSNs, SQL errors, stack traces, usernames, or internal exception details
- plain PHP implementation with no framework requirement
- designed to work cleanly as a service folder below `api.sasd.de`

## Intended Deployment Model

This project is intended for structures like the following:

```text
api.sasd.de/
  health/
  taskhost/
  auth/
```

In this model, each service lives in its own physical subfolder. This repository is the complete content of the `health/` folder.

## Project Structure

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
  DEVELOPER.md
  SECURITY.md
  TESTING.md
.env.example
composer.json
```

## Requirements

- PHP 8.1 or newer
- PDO
- `pdo_mysql`
- Apache with `mod_rewrite` enabled for the provided `.htaccess`
- MySQL or MariaDB

## Installation

### 1. Upload the project into the `health/` folder

Example target:

```text
api.sasd.de/health/
```

### 2. Install Composer autoload files (optional but recommended)

```bash
composer install --no-dev --optimize-autoloader
```

The service also contains a fallback loader and can start without Composer's generated autoloader, as long as the source files are present.

### 3. Create the environment file

```bash
cp .env.example .env
```

Then adjust the values in `.env`.

Example:

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

## Endpoint Note

Because `health` is a real service folder in the intended hosting model, some Apache setups may redirect `https://api.sasd.de/health` to `https://api.sasd.de/health/` before the application handles the request. The service normalizes trailing slashes, so both forms are acceptable.

## Endpoints

### Health Check

```http
GET /health
```

Example response:

```json
{
  "status": "ok",
  "database": "ok"
}
```

### Database Time

```http
GET /health/time
```

Example response:

```json
{
  "status": "ok",
  "database": "ok",
  "db_time": "24.03.2026:16:42"
}
```

### Protected phpinfo Endpoint

The endpoint is available only when explicitly enabled in `.env`:

```env
APP_PHPINFO_ENABLED=true
APP_PHPINFO_TOKEN=replace-with-a-long-random-token
```

Request with header:

```http
GET /health/phpinfo
X-Health-Token: replace-with-a-long-random-token
```

Or with query parameter:

```http
GET /health/phpinfo?token=replace-with-a-long-random-token
```

## Local Development

When you run the service locally directly from its own folder, the service root is simply `/`.

Example:

```bash
php -S 127.0.0.1:8080 index.php
```

Then test:

- `http://127.0.0.1:8080/`
- `http://127.0.0.1:8080/time`
- `http://127.0.0.1:8080/phpinfo`

When the same files are deployed inside the `/health` subfolder on `api.sasd.de`, the service automatically normalizes the base path and serves:

- `/health`
- `/health/time`
- `/health/phpinfo`

## Security Notes

This service is intentionally conservative.

External responses do not reveal:

- database credentials
- database host names
- DSNs
- SQL statements
- exception messages
- stack traces
- filesystem paths

If a database check fails, the service returns only a generic error response.

Additional hardening in this edition:

- `.htaccess` denies direct web access to `src/`, `vendor/`, `docs/`, `.env`, Composer files, and selected project metadata files
- `Options -MultiViews` is used to avoid Apache content negotiation side effects such as `300 Multiple Choices`
- `phpinfo()` remains disabled by default and must be protected with a token when enabled

## Documentation

- [Administration Guide](docs/ADMIN.md)
- [Developer Guide](docs/DEVELOPER.md)
- [API Reference](docs/API_REFERENCE.md)
- [Security Policy](docs/SECURITY.md)
- [Contributing Guide](docs/CONTRIBUTING.md)
- [Testing Guide](docs/TESTING.md)
- [Changelog](docs/CHANGELOG.md)

## License

This project is licensed under the MIT License. See the `LICENSE` file for details.
