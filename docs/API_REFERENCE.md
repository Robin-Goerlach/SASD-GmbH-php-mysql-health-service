# API Reference

## SASD GmbH PHP MySQL Health Service

This document describes the public API of the **SASD GmbH PHP MySQL Health Service** from the perspective of a consumer or integration user.

It explains the available endpoints, expected request formats, response structures, status codes, security behavior, and integration recommendations.

---

## 1. Overview

The service provides a small REST-style HTTP API for health and diagnostics.

Its main purpose is to allow external systems, administrators, or monitoring tools to verify that:

- the service is reachable
- the application is running
- the MySQL database connection is alive
- the database can return the current server time

An optional diagnostic endpoint for `phpinfo()` is also available, but it is disabled by default and protected by token-based access control.

---

## 2. Base URL

The exact base URL depends on your deployment.

Example:

```text
https://health.example.com
```

All endpoints described below are relative to that base URL.

---

## 3. Content Type

Unless otherwise noted, the API returns:

```http
Content-Type: application/json; charset=utf-8
```

The `phpinfo()` endpoint is the exception and returns HTML output when enabled and successfully accessed.

---

## 4. General Behavior

The service is intentionally minimal and security-focused.

It does **not** expose internal information such as:

- database credentials
- DSNs
- stack traces
- SQL statements
- SQL error details
- internal file paths
- framework internals

If an internal error occurs, the service returns only a generic error response.

---

## 5. Endpoints

### 5.1 Health Check

Checks whether the service can establish a live connection to the configured MySQL database.

#### Request

```http
GET /api/health
```

#### Authentication

No authentication required.

#### Successful Response

**Status:** `200 OK`

```json
{
  "status": "ok",
  "database": "ok"
}
```

#### Error Response

**Status:** `503 Service Unavailable`

```json
{
  "status": "error"
}
```

#### Intended Use

Use this endpoint for:

- uptime checks
- reverse proxy health checks
- container liveness checks
- orchestration probes
- external monitoring
- internal service diagnostics

#### Notes

This endpoint is the recommended default endpoint for automated monitoring.

---

### 5.2 Database Time

Checks the database connection and returns the current database server time in a formatted string.

#### Request

```http
GET /api/health/time
```

#### Authentication

No authentication required.

#### Successful Response

**Status:** `200 OK`

```json
{
  "status": "ok",
  "database": "ok",
  "db_time": "24.03.2026:16:42"
}
```

#### Response Fields

| Field | Type | Description |
|---|---|---|
| `status` | string | General result status. Expected value: `ok`. |
| `database` | string | Database connectivity result. Expected value: `ok`. |
| `db_time` | string | Current database server time formatted as `DD.MM.YYYY:HH:MM`. |

#### Error Response

**Status:** `503 Service Unavailable`

```json
{
  "status": "error"
}
```

#### Intended Use

Use this endpoint when you want to:

- verify that the DB connection works
- verify that queries can be executed
- compare application time with database time
- validate time-related infrastructure behavior

#### Notes

This endpoint should be used only when the database time is actually needed. For routine health monitoring, `/api/health` is usually sufficient.

---

### 5.3 PHP Information

Returns the output of PHP's built-in `phpinfo()` function.

This endpoint is intended only for temporary diagnostics and administration.

#### Request

```http
GET /api/phpinfo
```

#### Authentication

This endpoint is protected.

It requires:

- `APP_PHPINFO_ENABLED=true` in server configuration
- a valid token

The token can be supplied in one of two ways.

##### Option 1: HTTP Header

```http
X-Health-Token: your-secret-token
```

##### Option 2: Query Parameter

```http
GET /api/phpinfo?token=your-secret-token
```

#### Successful Response

**Status:** `200 OK`

**Content-Type:** typically `text/html`

Response body:

- standard `phpinfo()` HTML output

#### Error Responses

##### Endpoint disabled

**Status:** `404 Not Found`

Example response:

```json
{
  "status": "error"
}
```

##### Missing or invalid token

**Status:** `403 Forbidden`

Example response:

```json
{
  "status": "error"
}
```

#### Intended Use

Use this endpoint only for:

- temporary diagnostics
- PHP runtime inspection
- extension verification
- environment troubleshooting

#### Security Recommendation

Do not expose this endpoint publicly unless you have a very good reason.

Recommended protections:

- keep it disabled by default
- use a long random token
- restrict access by IP or VPN
- use HTTPS only
- disable it again after diagnostics are complete

---

## 6. Status Codes

The following HTTP status codes may be returned by the service.

| Status Code | Meaning | Typical Cause |
|---|---|---|
| `200 OK` | Request processed successfully | Health check passed or `phpinfo()` returned successfully |
| `403 Forbidden` | Access denied | Missing or invalid token for protected endpoint |
| `404 Not Found` | Route not found or endpoint intentionally unavailable | Wrong path or disabled `phpinfo()` endpoint |
| `405 Method Not Allowed` | HTTP method not allowed | Using `POST`, `PUT`, or another unsupported method |
| `503 Service Unavailable` | Health check failed | Database connection error or internal availability problem |

---

## 7. Supported HTTP Methods

The service is read-only and currently supports only:

- `GET`

Requests using methods such as `POST`, `PUT`, `PATCH`, or `DELETE` are not supported.

If such a method is used, the service may return:

```http
405 Method Not Allowed
```

---

## 8. Request Examples

### 8.1 Basic Health Check

```bash
curl https://health.example.com/api/health
```

### 8.2 Database Time

```bash
curl https://health.example.com/api/health/time
```

### 8.3 Protected phpinfo with Header

```bash
curl -H "X-Health-Token: your-secret-token" \
  https://health.example.com/api/phpinfo
```

### 8.4 Protected phpinfo with Query Parameter

```bash
curl "https://health.example.com/api/phpinfo?token=your-secret-token"
```

---

## 9. Response Conventions

### 9.1 Minimal Success Responses

The service uses deliberately small response bodies.

This is intentional and part of the security design.

### 9.2 Minimal Error Responses

On failure, the service does not explain internal details.

Example:

```json
{
  "status": "error"
}
```

This means consumers should rely on:

- HTTP status codes
- expected endpoint behavior
- server-side logs for deeper analysis

### 9.3 No Internal Diagnostics in API Output

The API is not intended to return:

- SQL error text
- exception messages
- stack traces
- host details
- connection strings

This is by design.

---

## 10. Integration Guidance

### 10.1 Monitoring Systems

Recommended endpoint:

```http
GET /api/health
```

Use this endpoint for periodic polling by:

- uptime robots
- load balancers
- reverse proxies
- Kubernetes probes
- Docker health checks
- internal observability tools

### 10.2 Administrative Diagnostics

Use:

```http
GET /api/health/time
```

when you need confirmation that the database is not only reachable, but also responding to SQL queries correctly.

### 10.3 PHP Runtime Inspection

Use:

```http
GET /api/phpinfo
```

only for controlled, temporary diagnostics.

This endpoint is not intended for routine monitoring.

---

## 11. Security Considerations for API Consumers

Consumers of this API should be aware of the following:

- `/api/health` and `/api/health/time` may be suitable for monitoring, but should still be protected from abuse if exposed on the public internet.
- `/api/phpinfo` should be considered sensitive.
- Tokens used for protected endpoints must be handled as secrets.
- Query-parameter tokens may appear in logs or browser history; headers are usually preferable.
- Always use HTTPS in production.

---

## 12. Caching

The service is intended for live operational checks.

Responses should not be cached by clients, proxies, or CDNs.

If you integrate the API into infrastructure, configure those systems to avoid caching health responses.

---

## 13. Versioning

At the moment, the service does not expose explicit API versioning in the URL.

Current path style:

```text
/api/health
/api/health/time
/api/phpinfo
```

If the service evolves in the future, versioning may be introduced through path-based routing such as:

```text
/api/v1/health
```

Until then, consumers should treat the current API as a small, deployment-specific operational interface.

---

## 14. Error Handling Expectations

As an API user, interpret responses as follows:

- `200` means the endpoint succeeded
- `503` means the service is reachable but the health check failed
- `403` means authorization failed for a protected endpoint
- `404` means the route does not exist or the endpoint is disabled
- `405` means the HTTP method is not supported

Do not expect descriptive internal error messages in the response body.

---

## 15. Compatibility Expectations

This API is designed for simple operational use.

Typical compatible clients include:

- `curl`
- shell scripts
- monitoring agents
- reverse proxies
- browser-based manual checks
- backend integration scripts

No special SDK is required.

---

## 16. Best Practices for Consumers

Use the API in the following way:

- prefer `/api/health` for routine availability checks
- use `/api/health/time` only when database time is relevant
- use header-based token authentication for `/api/phpinfo`
- do not automate routine polling of `/api/phpinfo`
- treat all protected endpoint tokens as secrets
- handle `503` as an operational alert, not as a parsing failure

---

## 17. Example Monitoring Logic

A monitoring system can interpret the API like this:

- `200` from `/api/health` → service healthy
- `503` from `/api/health` → application reachable, but DB health failed
- timeout / connection failure → service unreachable
- `403` on `/api/phpinfo` → authentication or authorization problem

---

## 18. Contact Between Consumer and Operator

If you are a consumer of the service and repeated failures occur, contact the service operator and provide:

- timestamp of the request
- endpoint used
- HTTP status code returned
- whether the issue is persistent or intermittent
- relevant correlation or request identifiers if your infrastructure adds them

---

## 19. Summary

The API is intentionally small, stable in purpose, and conservative in what it reveals.

For most users, the important points are:

- use `GET /api/health` for health checks
- use `GET /api/health/time` for DB time checks
- use `GET /api/phpinfo` only when explicitly enabled and authorized
- rely on HTTP status codes rather than verbose error bodies
- treat the service as an operational endpoint, not a general diagnostics API

