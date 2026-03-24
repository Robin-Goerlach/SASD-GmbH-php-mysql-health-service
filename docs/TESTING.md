# TESTING.md

## Testing Guide

This document describes how to validate the **SASD GmbH PHP MySQL Health Service** in development, staging, and production-like deployments.

This edition assumes production deployment under a subfolder such as `https://api.sasd.de/health`, while local development may run directly at `/`.

---

## 1. Testing Goals

The main goals of testing this service are:

- verify that routing works correctly in the intended subfolder deployment model
- verify that database checks work with valid credentials
- verify that failure behavior remains intentionally minimal
- verify that `phpinfo()` stays protected
- verify that internal files are not directly exposed
- verify that Apache path handling does not produce `300 Multiple Choices` or other unexpected behavior

---

## 2. Minimal Local Smoke Test

Run the service locally:

```bash
php -S 127.0.0.1:8080 index.php
```

Then test:

```bash
curl -i http://127.0.0.1:8080/
curl -i http://127.0.0.1:8080/time
curl -i http://127.0.0.1:8080/phpinfo
```

Expected local behavior:

- `/` returns `200` or `503` depending on DB availability
- `/time` returns `200` or `503` depending on DB availability
- `/phpinfo` returns `404` when disabled

---

## 3. Production-Oriented Smoke Test

When testing on Apache, keep in mind that `/health` may redirect to `/health/` before the application takes over. That behavior is acceptable as long as the redirected request reaches the service and returns the expected application response.


For the intended deployment model, test the production-style URLs:

```bash
curl -i https://api.sasd.de/health
curl -i https://api.sasd.de/health/time
curl -i https://api.sasd.de/health/phpinfo
```

Expected behavior:

- `/health` returns JSON health information or generic `503`
- `/health/time` returns JSON database time information or generic `503`
- `/health/phpinfo` returns `404` unless explicitly enabled

---

## 4. Happy Path Tests

### 4.1 Health Check with Valid DB Configuration

Expected:

- HTTP status `200`
- JSON body contains:

```json
{
  "status": "ok",
  "database": "ok"
}
```

### 4.2 Database Time with Valid DB Configuration

Expected:

- HTTP status `200`
- JSON body contains `db_time`

### 4.3 phpinfo with Explicit Enablement and Valid Token

Set in `.env`:

```env
APP_PHPINFO_ENABLED=true
APP_PHPINFO_TOKEN=your-token
```

Test:

```bash
curl -i -H "X-Health-Token: your-token" https://api.sasd.de/health/phpinfo
```

Expected:

- HTTP status `200`
- HTML output from `phpinfo()`

---

## 5. Failure Path Tests

### 5.1 Invalid Database Credentials

Break the DB credentials intentionally in `.env` and test:

```bash
curl -i https://api.sasd.de/health
curl -i https://api.sasd.de/health/time
```

Expected:

- HTTP status `503`
- body:

```json
{
  "status": "error"
}
```

No stack trace, DSN, SQL error, or sensitive internal message should appear.

### 5.2 Unknown Route

Test:

```bash
curl -i https://api.sasd.de/health/unknown
```

Expected:

- HTTP status `404`
- body:

```json
{
  "status": "error",
  "message": "Not Found"
}
```

### 5.3 phpinfo Disabled

Test:

```bash
curl -i https://api.sasd.de/health/phpinfo
```

Expected:

- HTTP status `404`
- plain text body `Not Found`

### 5.4 phpinfo Wrong Token

Test:

```bash
curl -i -H "X-Health-Token: wrong-token" https://api.sasd.de/health/phpinfo
```

Expected:

- HTTP status `403`
- plain text body `Forbidden`

---

## 6. Security Exposure Checks

The service root is web-facing in the intended deployment model, so these checks are important.

### 6.1 Direct Access to Internal Source Code

Test:

```bash
curl -i https://api.sasd.de/health/src/Bootstrap.php
```

Expected:

- `403`, `404`, or another denied response depending on Apache behavior
- source code must **not** be served

### 6.2 Direct Access to `.env`

Test:

```bash
curl -i https://api.sasd.de/health/.env
```

Expected:

- access denied or not found
- the file must never be downloadable

### 6.3 Direct Access to Documentation

Test:

```bash
curl -i https://api.sasd.de/health/docs/DEVELOPER.md
```

Expected:

- access denied or not found

---

## 7. Apache Path Handling Checks

### 7.1 MultiViews Behavior

The service should not produce `300 Multiple Choices` responses caused by Apache content negotiation.

Test:

```bash
curl -i https://api.sasd.de/health/time
```

Expected:

- application response, not Apache content-negotiation output

If you see `300 Multiple Choices`, verify that `Options -MultiViews` is still present in `.htaccess`.

### 7.2 Placeholder Files

If the service does not respond and a generic hosting page appears, check whether a placeholder `index.html` is masking the application.

---

## 8. HEAD Request Checks

The service supports `HEAD` for `/health` and `/health/time`.

Test:

```bash
curl -I https://api.sasd.de/health
curl -I https://api.sasd.de/health/time
```

Expected:

- appropriate status codes
- headers present
- no response body

---

## 9. Manual Regression Checklist

Before publishing a new version, confirm:

- routing works locally
- routing works under `/health` in staging or production-like hosting
- valid DB config returns success
- invalid DB config returns generic `503`
- unknown routes return generic `404`
- `phpinfo()` is disabled by default
- `phpinfo()` accepts only the correct token
- `.env` is not accessible
- `src/` and `docs/` are not accessible
- no verbose errors leak to the client

---

## 10. Final Recommendation

Because the service is intentionally small, manual smoke tests are already valuable. Focus on routing correctness, minimal failure behavior, and protection of internal files. Those areas are especially important in the shared-hosting subfolder deployment model.
