# SASD-GmbH PHP Health Service
Minimal, security-focused RESTful PHP health service for MySQL. Provides a safe live database check, a database time endpoint, and optional protected phpinfo access without exposing sensitive connection details or internal error information.

This project reads database connection settings from a `.env` file, performs a live MySQL connectivity check, and exposes minimal HTTP endpoints for health monitoring without leaking sensitive configuration details.

## Features

- `GET /api/health` — verifies that the application can connect to the database
- `GET /api/health/time` — returns the current database server time
- `GET /api/phpinfo` — optional `phpinfo()` endpoint, disabled by default and protected by a token
- minimal JSON responses with no DSN, SQL error text, stack traces, or credentials exposed
- simple structure based on plain PHP and PDO

## Security Approach

The service is intentionally conservative.

External responses do not reveal:

- database host names
- database names
- usernames
- SQL statements
- exception messages
- stack traces

If the database check fails, the service returns only a generic error response. Internally, failures can still be logged by the runtime environment.

The `phpinfo()` endpoint is:

- disabled by default
- enabled only through configuration
- protected by a token

## Requirements

- PHP 8.1 or newer
- PDO
- `pdo_mysql`
- a web server with its document root pointing to `public/`

## Project Structure

```text
public/
  index.php
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
composer.json
README.md
```

## Installation

### 1. Install dependencies

```bash
composer install
```

### 2. Create your environment file

```bash
cp .env.example .env
```

Then adjust the values in `.env`.

Example:

```env
APP_ENV=prod
APP_DEBUG=false
APP_PHPINFO_ENABLED=false
APP_PHPINFO_TOKEN=change-this-to-a-long-random-token

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=your_database
DB_USER=your_user
DB_PASS=your_password
DB_CHARSET=utf8mb4
```

### 3. Run locally

```bash
php -S 127.0.0.1:8080 -t public public/index.php
```

## Endpoints

### Health check

```http
GET /api/health
```

Example response:

```json
{
  "status": "ok",
  "database": "ok"
}
```

### Database time

```http
GET /api/health/time
```

Example response:

```json
{
  "status": "ok",
  "database": "ok",
  "db_time": "24.03.2026:16:42"
}
```

### Protected phpinfo endpoint

The endpoint is available only when explicitly enabled in `.env`:

```env
APP_PHPINFO_ENABLED=true
APP_PHPINFO_TOKEN=replace-with-a-long-random-token
```

Request with header:

```http
GET /api/phpinfo
X-Health-Token: replace-with-a-long-random-token
```

Or with query parameter:

```http
GET /api/phpinfo?token=replace-with-a-long-random-token
```

## Notes on Database Time

The MySQL query behind the time endpoint is equivalent to:

```sql
SELECT DATE_FORMAT(NOW(), '%d.%m.%Y:%H:%i') AS db_time;
```

For Oracle, the comparable query would be:

```sql
SELECT TO_CHAR(SYSDATE, 'DD.MM.YYYY:HH24:MI') AS db_time FROM dual;
```

## Deployment Notes

- point your web server document root to `public/`
- keep `.env` outside the public web root if possible
- never enable `phpinfo()` in production unless there is a very good reason
- if you enable it temporarily, protect it with a strong token and disable it again afterwards

## Intended Use Cases

This service is useful for:

- uptime monitoring
- deployment smoke tests
- reverse proxy and load balancer checks
- container health probes
- simple infrastructure diagnostics

## License

MIT
