# TESTING.md

## Testing Guide

This document explains how to test the **SASD GmbH PHP MySQL Health Service** during development, deployment, and routine operation.

The service is intentionally small, so testing should also remain simple, focused, and practical. The goal is not to build a large test framework around the service, but to verify that the essential behavior is correct, stable, and secure.

---

## 1. Testing Goals

The service should be tested to confirm the following:

- the application starts correctly
- routing works as expected
- the database connection check works
- the database time endpoint returns a valid value
- the `phpinfo()` endpoint remains disabled unless explicitly enabled
- sensitive information is not exposed in error cases
- invalid routes return the expected response
- configuration errors fail safely

---

## 2. Types of Tests

For this project, testing can be divided into four practical categories.

### 2.1 Syntax Checks

These tests verify that the PHP files are syntactically valid.

Example:

```bash
find . -name "*.php" -print0 | xargs -0 -n1 php -l
```

This is a quick and useful baseline check before every commit or deployment.

### 2.2 Manual Endpoint Tests

These tests verify that the service behaves correctly from a user's perspective.

Typical tools:

- `curl`
- browser
- Postman
- Insomnia
- Bruno

### 2.3 Integration Tests

These tests verify the interaction between the application and a real MySQL or MariaDB database.

They are especially useful after:

- changing database credentials
- moving to a new server
- changing routing logic
- changing environment handling
- changing response behavior

### 2.4 Security Behavior Checks

These tests confirm that the service does not reveal internal details.

This is very important for this project because the main value of the service is not only that it works, but that it stays quiet when something goes wrong.

---

## 3. Pre-Test Requirements

Before testing, make sure the following is available:

- PHP 8.1 or newer
- Composer dependencies installed
- `.env` file configured
- MySQL or MariaDB reachable
- web server document root pointing to `public/`
- `pdo_mysql` enabled

You can verify PHP modules with:

```bash
php -m | grep pdo
```

---

## 4. Basic Test Scenarios

## 4.1 Health Endpoint Returns Success

Request:

```bash
curl http://127.0.0.1:8080/api/health
```

Expected behavior:

- HTTP status `200`
- JSON response contains:

```json
{
  "status": "ok",
  "database": "ok"
}
```

This confirms that:

- routing works
- environment values are read
- database connection is possible
- the response format is correct

---

## 4.2 Database Time Endpoint Returns DB Time

Request:

```bash
curl http://127.0.0.1:8080/api/health/time
```

Expected behavior:

- HTTP status `200`
- JSON response contains `db_time`
- format should look like `24.03.2026:16:42`

Example response:

```json
{
  "status": "ok",
  "database": "ok",
  "db_time": "24.03.2026:16:42"
}
```

This confirms that the service not only connects to the database, but also successfully runs a query.

---

## 4.3 Unknown Route Returns Not Found

Request:

```bash
curl -i http://127.0.0.1:8080/api/does-not-exist
```

Expected behavior:

- HTTP status `404`
- minimal error response

This confirms that undefined routes are rejected correctly.

---

## 4.4 Wrong Database Credentials Fail Safely

Temporarily place invalid values in `.env`, for example:

```env
DB_PASS=wrong-password
```

Then call:

```bash
curl -i http://127.0.0.1:8080/api/health
```

Expected behavior:

- HTTP status `503`
- generic JSON error response
- no DSN
- no database host
- no username
- no SQL error
- no stack trace

Example response:

```json
{
  "status": "error"
}
```

This is one of the most important tests in the entire project.

---

## 4.5 `phpinfo()` Is Disabled by Default

Request:

```bash
curl -i http://127.0.0.1:8080/api/phpinfo
```

Expected behavior:

- endpoint should not reveal PHP information unless explicitly enabled
- access should fail when disabled

This confirms that the administrative endpoint is not accidentally exposed.

---

## 4.6 `phpinfo()` Works Only with Valid Token

Set in `.env`:

```env
APP_PHPINFO_ENABLED=true
APP_PHPINFO_TOKEN=test-token
```

Request without token:

```bash
curl -i http://127.0.0.1:8080/api/phpinfo
```

Expected behavior:

- access denied

Request with token header:

```bash
curl -i -H "X-Health-Token: test-token" http://127.0.0.1:8080/api/phpinfo
```

Expected behavior:

- successful response
- PHP information page is returned

This confirms that the endpoint is protected correctly.

---

## 5. Local Development Testing

A simple local workflow can look like this.

### 5.1 Start the Built-in PHP Server

```bash
php -S 127.0.0.1:8080 -t public public/index.php
```

### 5.2 Run Basic Requests

```bash
curl http://127.0.0.1:8080/api/health
curl http://127.0.0.1:8080/api/health/time
curl -i http://127.0.0.1:8080/api/phpinfo
```

### 5.3 Check PHP Syntax

```bash
find . -name "*.php" -print0 | xargs -0 -n1 php -l
```

This is enough for a practical first testing round.

---

## 6. Suggested Regression Checklist

After every meaningful code change, test at least the following:

- `/api/health` returns `200`
- `/api/health/time` returns `200`
- invalid route returns `404`
- wrong DB credentials return `503`
- `phpinfo()` remains disabled unless intentionally enabled
- enabled `phpinfo()` still requires a valid token
- no internal error details appear in responses

This checklist is especially helpful before pushing changes to GitHub or deploying to a server.

---

## 7. Production Verification

After deployment, run a small production-oriented smoke test.

Example:

```bash
curl -i https://health.example.com/api/health
curl -i https://health.example.com/api/health/time
```

Confirm:

- HTTPS works
- correct status codes are returned
- expected JSON format is returned
- no debug output is visible
- `phpinfo()` is disabled unless explicitly needed

Also inspect:

- web server logs
- PHP logs
- reverse proxy logs
- firewall behavior if restrictions are configured

---

## 8. Response Validation

In a small service like this, response consistency matters.

### Successful Health Check

- status code: `200`
- content type: JSON
- keys: `status`, `database`

### Successful Time Request

- status code: `200`
- content type: JSON
- keys: `status`, `database`, `db_time`

### Error Case

- status code: typically `503`
- content type: JSON
- no sensitive internal details

### Unknown Route

- status code: `404`
- minimal error response

---

## 9. Negative Testing Ideas

Negative testing is particularly useful here because the service must fail safely.

Try these scenarios:

- database host unreachable
- wrong database port
- wrong database name
- wrong database password
- missing `.env`
- incomplete `.env`
- missing `pdo_mysql`
- invalid token for `phpinfo()`
- request to undefined route

The expected result is not just failure. The expected result is controlled, quiet, minimal failure.

---

## 10. Future Automated Testing

If the project grows, automated tests can be added.

Possible next steps:

- PHPUnit for response behavior
- integration test setup with a dedicated MySQL container
- CI pipeline for syntax and smoke tests
- simple shell test scripts for deployment checks

For the current size of the service, a lightweight approach is usually enough.

A good balance would be:

- syntax checks
- a few manual endpoint tests
- one repeatable smoke test script

---

## 11. Example Smoke Test Script

A very small shell-based smoke test could look like this:

```bash
#!/usr/bin/env bash
set -e

BASE_URL="http://127.0.0.1:8080"

echo "Testing /api/health"
curl -fsS "$BASE_URL/api/health" > /dev/null

echo "Testing /api/health/time"
curl -fsS "$BASE_URL/api/health/time" > /dev/null

echo "Smoke tests passed"
```

This is often enough for a minimal service.

---

## 12. Common Testing Mistakes

Avoid these mistakes:

- testing only the happy path
- not testing wrong credentials
- enabling `phpinfo()` and forgetting to disable it again
- checking only browser output and not status codes
- forgetting to verify that error responses stay generic
- skipping syntax validation before deployment

---

## 13. Recommended Minimum Testing Standard

For this project, a practical minimum standard would be:

1. syntax check all PHP files
2. test `/api/health`
3. test `/api/health/time`
4. test one failure scenario with invalid DB credentials
5. verify that `phpinfo()` is disabled by default
6. verify that no internal error details are exposed

That is already enough to cover the most important risks in this service.

---

## 14. Final Recommendation

Keep testing proportional to the size of the service.

This is a small infrastructure component. It does not need a huge testing framework, but it does need disciplined checks around:

- correct routing
- safe database connectivity
- controlled failure behavior
- limited information disclosure
- protected administrative functionality

In this project, good testing means confirming not only that the service works, but that it fails quietly and safely.
