# ADMIN.md

## Administration Guide

This document explains how to install, configure, operate, and secure the **SASD GmbH PHP MySQL Health Service** in a production-oriented environment.

The service is intentionally minimal. Its purpose is to provide a safe database connectivity check, return the current database time, and optionally expose a protected `phpinfo()` endpoint for administration and diagnostics.

---

## 1. Purpose of the Service

This service provides three main endpoints:

- `GET /api/health`  
  Performs a live database connectivity check and returns a minimal success or error response.

- `GET /api/health/time`  
  Returns the current time from the MySQL server.

- `GET /api/phpinfo`  
  Optional administrative diagnostics endpoint. Disabled by default and protected by token authentication.

The service is designed to reveal as little internal information as possible.

---

## 2. System Requirements

### Required Software

- PHP 8.1 or newer
- Composer
- MySQL or MariaDB
- Web server such as Apache or Nginx
- PHP extensions:
  - `pdo`
  - `pdo_mysql`

### Recommended Environment

- Linux server
- HTTPS-enabled reverse proxy or web server
- Dedicated service account
- Restricted firewall rules
- Monitoring integration

---

## 3. Installation

### 3.1 Clone the Repository

```bash
git clone https://github.com/Robin-Goerlach/SASD-GmbH-php-mysql-health-service.git
cd SASD-GmbH-php-mysql-health-service
```

### 3.2 Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
```

### 3.3 Create the Environment File

```bash
cp .env.example .env
```

### 3.4 Adjust the Environment Configuration

Edit the `.env` file and set the correct values:

```env
APP_ENV=production
APP_DEBUG=false

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

## 4. Web Server Setup

## Apache

The document root should point to the `public/` directory.

Example virtual host:

```apache
<VirtualHost *:80>
    ServerName health.example.com
    DocumentRoot /var/www/php-health-service/public

    <Directory /var/www/php-health-service/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/php-health-service-error.log
    CustomLog ${APACHE_LOG_DIR}/php-health-service-access.log combined
</VirtualHost>
```

If HTTPS is available, use it and redirect all HTTP traffic to HTTPS.

## Nginx

Example server block:

```nginx
server {
    listen 80;
    server_name health.example.com;

    root /var/www/php-health-service/public;
    index index.php;

    location / {
        try_files $uri /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root/index.php;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }

    location ~ /\. {
        deny all;
    }
}
```

Adapt the PHP-FPM socket path to your system.

---

## 5. File Placement and Permissions

### Recommended Directory Layout

```text
/var/www/php-health-service
├── public/
├── src/
├── vendor/
├── .env
├── composer.json
└── LICENSE
```

### Permissions

- The web server user should be able to read the application files.
- The `.env` file must not be publicly accessible.
- Write permissions should be avoided unless logging or cache directories are explicitly needed.

Example:

```bash
chown -R www-data:www-data /var/www/php-health-service
find /var/www/php-health-service -type d -exec chmod 755 {} \;
find /var/www/php-health-service -type f -exec chmod 644 {} \;
chmod 600 /var/www/php-health-service/.env
```

---

## 6. Security Hardening

## 6.1 Protect the `.env` File

The `.env` file contains sensitive credentials and tokens.

Recommended measures:

- Store `.env` outside the public web root if possible.
- Deny access to hidden files in the web server configuration.
- Never commit `.env` to Git.
- Use strong database passwords.

## 6.2 Disable Debug Output

In production:

```env
APP_ENV=production
APP_DEBUG=false
```

The service should never expose stack traces, DSNs, SQL errors, or database credentials.

## 6.3 Use HTTPS

Always run the service behind HTTPS in production.  
Do not expose administrative endpoints over plain HTTP.

## 6.4 Restrict Network Access

If possible, allow access only from trusted monitoring systems, reverse proxies, VPN networks, or internal management networks.

Examples:

- Restrict access by firewall
- Restrict access by reverse proxy
- Restrict access by IP allowlist
- Expose the service only internally

## 6.5 Secure the `phpinfo()` Endpoint

The `phpinfo()` endpoint is powerful and should be treated carefully.

Recommendations:

- Keep it disabled unless needed
- Protect it with a strong token
- Prefer additional IP restrictions
- Disable it again after diagnostics are complete

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
GET /api/phpinfo
X-Health-Token: your-token
```

## 6.6 Minimize Information Disclosure

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

## 7. Database User Recommendations

Create a dedicated database user with only the permissions required for the health check.

For this service, the user usually needs only very limited access.

Example principle:

- no schema changes
- no administrative privileges
- no write permissions unless explicitly required
- no broad grants across unrelated databases

A minimal account is better than reusing a full administrative MySQL account.

---

## 8. Operational Checks

## 8.1 Test the Basic Health Endpoint

```bash
curl https://health.example.com/api/health
```

Expected response:

```json
{
  "status": "ok",
  "database": "ok"
}
```

## 8.2 Test the Database Time Endpoint

```bash
curl https://health.example.com/api/health/time
```

Expected response:

```json
{
  "status": "ok",
  "database": "ok",
  "db_time": "24.03.2026:16:42"
}
```

## 8.3 Test the `phpinfo()` Endpoint

Only if explicitly enabled:

```bash
curl -H "X-Health-Token: your-token" https://health.example.com/api/phpinfo
```

---

## 9. Monitoring and Integration

This service is suitable for use with:

- reverse proxy health checks
- uptime monitoring tools
- internal monitoring systems
- orchestration liveness checks
- external status dashboards, if carefully protected

Recommended use:

- use `/api/health` for routine monitoring
- use `/api/health/time` only when DB server time is actually required
- avoid exposing `/api/phpinfo` to monitoring tools

---

## 10. Logging

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

## 11. Updating the Service

### 11.1 Backup Current Configuration

Before updating:

- back up `.env`
- back up deployment-specific web server configuration
- document any local adjustments

### 11.2 Pull the Latest Version

```bash
git pull
composer install --no-dev --optimize-autoloader
```

### 11.3 Validate After Update

After deployment:

- test `/api/health`
- test `/api/health/time`
- confirm that `.env` is still correct
- confirm that `phpinfo()` is disabled unless intentionally enabled
- inspect logs for unexpected errors

---

## 12. Troubleshooting

## Problem: `status=error`

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
- inspect web server and PHP error logs

## Problem: `phpinfo()` does not work

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

## Problem: blank page or 500 error

Possible causes:

- PHP fatal error
- missing dependencies
- bad file permissions
- web server points to wrong document root

Actions:

- check logs
- verify `composer install`
- verify `public/` as document root
- verify PHP version and extensions

---

## 13. Recommended Production Checklist

Before going live, confirm the following:

- `.env` exists and is not public
- `APP_DEBUG=false`
- HTTPS is enabled
- `phpinfo()` is disabled unless absolutely necessary
- strong database password is used
- dedicated limited MySQL user is used
- web server document root points to `public/`
- logs are enabled
- firewall rules are in place
- service is tested with `curl`

---

## 14. License and Administration Responsibility

This service is intentionally small, but it still handles credentials and infrastructure health information.

The administrator is responsible for:

- secure deployment
- access control
- HTTPS setup
- backup of configuration
- patching PHP and the web server
- reviewing logs and unauthorized access attempts

---

## 15. Final Recommendation

Use this service as a minimal health endpoint, not as a diagnostics portal.

Keep it quiet, small, and restrictive.  
Expose only what is necessary.  
Disable optional features unless they are explicitly needed.
