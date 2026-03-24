# ADMIN.md

## Administration Guide

This document explains how to install, configure, operate, and secure the **SASD GmbH PHP MySQL Health Service** when it is deployed as a subfolder service such as `https://api.sasd.de/health`.

The service is intentionally minimal. Its purpose is to provide a safe database connectivity check, return the current database time, and optionally expose a protected `phpinfo()` endpoint for administration and diagnostics.

---

## 1. Purpose of the Service

This service provides three main endpoints:

- `GET /health`  
  Performs a live database connectivity check and returns a minimal success or error response.

- `GET /health/time`  
  Returns the current time from the MySQL server.

- `GET /health/phpinfo`  
  Optional administrative diagnostics endpoint. Disabled by default and protected by token authentication.

The service is designed to reveal as little internal information as possible.

---

## 2. Intended Hosting Model

This edition is intended for a shared API host where multiple backend services live below the same domain in separate folders.

Example:

```text
api.sasd.de/
  health/
  taskhost/
```

In this model, the repository content is uploaded into the `health/` directory. The service then becomes available at:

- `https://api.sasd.de/health`
- `https://api.sasd.de/health/time`
- `https://api.sasd.de/health/phpinfo`

This is different from a deployment where the virtual host document root points to a dedicated `public/` directory.

---

## 3. System Requirements

### Required Software

- PHP 8.1 or newer
- Composer
- MySQL or MariaDB
- Apache with `mod_rewrite`
- PHP extensions:
  - `pdo`
  - `pdo_mysql`

### Recommended Environment

- Linux hosting environment
- HTTPS enabled
- Dedicated service account where possible
- Restricted firewall rules or reverse proxy restrictions
- Monitoring integration

---

## 4. Installation

### 4.1 Upload the Service into the `health/` Folder

Example target path:

```text
/path/to/api.sasd.de/health/
```

### 4.2 Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
```

This project can also start without Composer's generated autoloader because it contains a small fallback loader in `index.php`. Composer is still recommended.

### 4.3 Create the Environment File

```bash
cp .env.example .env
```

### 4.4 Adjust the Environment Configuration

Edit the `.env` file and set the correct values:

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

---

## 5. Apache Setup

This service is intended to run from inside the `health/` subfolder. The provided `.htaccess` file is part of the service and should remain in the service root.

Important behaviors provided by the `.htaccess` file:

- enables front-controller routing to `index.php`
- disables `MultiViews` to avoid `300 Multiple Choices` behavior
- denies direct access to `src/`, `vendor/`, `docs/`, `.env`, Composer files, and selected metadata files
- keeps real files and directories accessible only when they are explicitly allowed

### Required Apache Capabilities

- `mod_rewrite` must be active
- `AllowOverride` must permit `.htaccess`

### Shared Hosting Note

On shared hosting, you usually do not control the parent virtual host. In that case, the important step is that the subdomain `api.sasd.de` points to the correct filesystem location that contains the `health/` folder.

If the subdomain still points to a parking page or wrong folder, the service will not be reached at all.

---

## 6. File Placement and Permissions

### Recommended Directory Layout

```text
api-root/
  health/
    index.php
    .htaccess
    src/
    vendor/
    docs/
    .env
    composer.json
    LICENSE
```

### Permissions

- The web server user should be able to read the application files.
- The `.env` file must not be publicly accessible.
- Write permissions should be avoided unless logging or runtime files are explicitly needed.

Example:

```bash
chown -R www-data:www-data /path/to/api-root/health
find /path/to/api-root/health -type d -exec chmod 755 {} \;
find /path/to/api-root/health -type f -exec chmod 644 {} \;
chmod 600 /path/to/api-root/health/.env
```

---

## 7. Security Hardening

### 7.1 Protect the `.env` File

The `.env` file contains sensitive credentials and tokens.

Recommended measures:

- keep `.env` out of version control
- keep the provided `.htaccess` file in place
- use strong database passwords
- use unique credentials per service

### 7.2 Disable Debug Output

In production:

```env
APP_ENV=production
APP_DEBUG=false
```

The service should never expose stack traces, DSNs, SQL errors, or database credentials.

### 7.3 Use HTTPS

Always run the service behind HTTPS in production.  
Do not expose administrative endpoints over plain HTTP.

### 7.4 Restrict Network Access

If possible, allow access only from trusted monitoring systems, reverse proxies, VPN networks, or internal management networks.

Examples:

- restrict access by firewall
- restrict access by reverse proxy
- restrict access by IP allowlist
- expose the service only internally if public access is not required

### 7.5 Secure the `phpinfo()` Endpoint

The `phpinfo()` endpoint is powerful and should be treated carefully.

Recommendations:

- keep it disabled unless needed
- protect it with a strong token
- prefer additional IP restrictions
- disable it again after diagnostics are complete

Example:

```env
APP_PHPINFO_ENABLED=false
```

If temporary access is needed:

```env
APP_PHPINFO_ENABLED=true
APP_PHPINFO_TOKEN=use-a-long-random-secret-token
```

Request example:

```http
GET /health/phpinfo
X-Health-Token: your-token
```

### 7.6 Minimize Information Disclosure

This service is intentionally minimal. Maintain that principle.

Do not change the service to expose:

- database version
- connection details
- usernames
- hostnames
- SQL errors
- stack traces
- internal file paths

---

## 8. Database User Recommendations

Create a dedicated database user with only the permissions required for the health check.

For this service, the user usually needs only very limited access.

Example principle:

- no schema changes
- no administrative privileges
- no write permissions unless explicitly required
- no broad grants across unrelated databases

A minimal account is better than reusing a full administrative MySQL account.

---

## 9. URL Normalization Note

Because `health` is a real directory below the shared host root, some Apache setups may redirect `/health` to `/health/` before the application processes the request. This is normal directory behavior. The application normalizes the trailing slash and will still route the request correctly.

---

## 10. Operational Checks

### 9.1 Test the Basic Health Endpoint

```bash
curl https://api.sasd.de/health
```

Expected response:

```json
{
  "status": "ok",
  "database": "ok"
}
```

### 9.2 Test the Database Time Endpoint

```bash
curl https://api.sasd.de/health/time
```

Expected response:

```json
{
  "status": "ok",
  "database": "ok",
  "db_time": "24.03.2026:16:42"
}
```

### 9.3 Test the `phpinfo()` Endpoint

Only if explicitly enabled:

```bash
curl -H "X-Health-Token: your-token" https://api.sasd.de/health/phpinfo
```

---

## 11. Monitoring and Integration

This service is suitable for use with:

- reverse proxy health checks
- uptime monitoring tools
- internal monitoring systems
- orchestration liveness checks
- external status dashboards, if carefully protected

Recommended use:

- use `/health` for routine monitoring
- use `/health/time` only when DB server time is actually required
- avoid exposing `/health/phpinfo` to monitoring tools

---

## 12. Logging

The service should log failures internally without exposing details to the client.

Recommendations:

- send PHP errors to server logs
- disable display of errors in production
- centralize logs if possible
- monitor repeated failures and unauthorized access attempts

Recommended PHP settings in production:

```ini
display_errors = Off
log_errors = On
```

---

## 13. Updating the Service

### 12.1 Backup Current Configuration

Before updating:

- back up `.env`
- back up deployment-specific Apache configuration if any
- document local adjustments

### 12.2 Pull the Latest Version

```bash
git pull
composer install --no-dev --optimize-autoloader
```

### 12.3 Validate After Update

After deployment:

- test `/health`
- test `/health/time`
- confirm that `.env` is still correct
- confirm that `phpinfo()` is disabled unless intentionally enabled
- inspect logs for unexpected errors

---

## 14. Troubleshooting

### Problem: `status=error`

Possible causes:

- wrong database credentials
- database server unavailable
- firewall blocks access
- `pdo_mysql` missing
- wrong host or port
- invalid `.env` syntax

Actions:

- verify `.env`
- test MySQL reachability
- check PHP extensions
- inspect Apache and PHP error logs

### Problem: `phpinfo()` does not work

Possible causes:

- `APP_PHPINFO_ENABLED=false`
- wrong or missing token
- reverse proxy strips custom header
- route not reachable

Actions:

- verify `.env`
- verify token
- test with query parameter if needed
- inspect logs

### Problem: `300 Multiple Choices`

Possible cause:

- Apache `MultiViews` is enabled and interferes with path resolution

Action:

- keep `Options -MultiViews` in the provided `.htaccess`

### Problem: parking page or generic hosting error page appears

Possible causes:

- the subdomain points to the wrong folder
- DNS or hosting assignment is incomplete
- the service files were uploaded to a different location than the active document root

Actions:

- verify the filesystem target of `api.sasd.de`
- verify that the `health/` folder exists below the active subdomain root
- remove or rename a placeholder `index.html` if it masks the application

---

## 15. Recommended Production Checklist

Before going live, confirm the following:

- `.env` exists and is not public
- `APP_DEBUG=false`
- HTTPS is enabled
- `phpinfo()` is disabled unless absolutely necessary
- strong database password is used
- dedicated limited MySQL user is used
- the subdomain points to the correct parent folder
- the `health/` folder contains the service files
- logs are enabled
- firewall rules are in place
- service is tested with `curl`

---

## 16. Final Recommendation

Use this service as a minimal health endpoint, not as a diagnostics portal.

Keep it quiet, small, and restrictive.  
Expose only what is necessary.  
Disable optional features unless they are explicitly needed.
