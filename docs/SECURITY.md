# SECURITY.md

## Security Policy

Thank you for helping improve the security of the **SASD GmbH PHP MySQL Health Service**.

This project is intentionally small, but it still deals with infrastructure visibility, deployment details, and database connectivity. That makes security relevant even for a compact codebase.

---

## Reporting a Vulnerability

Please do **not** report security vulnerabilities through public issues, public pull requests, or any other public channel.

Instead, contact the maintainer privately through an agreed trusted channel.

When reporting a problem, please include:

- a clear description of the issue
- affected version or commit
- reproduction steps
- expected behavior
- actual behavior
- potential impact
- any suggested mitigation, if available

Please keep reports factual and as complete as possible.

---

## Scope

Relevant security topics include, for example:

- authentication bypass on the `phpinfo()` endpoint
- unintended exposure of `.env`, `src/`, `vendor/`, or `docs/`
- information leakage through error responses
- path handling or rewrite problems that reveal internal files
- unsafe default configuration
- denial-of-service weaknesses caused by malformed requests
- insecure deployment recommendations in the documentation

---

## Out of Scope

The following are usually out of scope unless they create a real security impact:

- purely cosmetic issues
- spelling mistakes
- minor documentation inconsistencies
- theoretical concerns without a realistic exploitation path
- vulnerabilities in software outside this project, unless this project introduces the problem through its own configuration or code

---

## Security Design Goals

This service follows a few core security principles.

### Minimal Information Disclosure

The service should not expose:

- DSNs
- database credentials
- stack traces
- SQL errors
- filesystem paths
- internal exception details

If a check fails, the external response should remain intentionally minimal.

### Safe Defaults

- `phpinfo()` should be disabled by default
- debug output should be disabled in production
- `.env` must never be committed
- the service should run with a minimally privileged database account
- HTTPS should be used in production
- access should be restricted where possible

### Shared Hosting Awareness

In this deployment model, the service root is web-facing as the `/health` folder below a shared `api.sasd.de` host.

That means Apache access rules are part of the security boundary. The provided `.htaccess` file is therefore security-relevant, not just convenient.

### Operational Simplicity

This project is small on purpose. Please avoid changes that turn a minimal health service into a broad diagnostics interface.

---

## Recommended Deployment Hardening

Operators of this service should:

- keep `APP_DEBUG=false` in production
- keep `APP_PHPINFO_ENABLED=false` unless temporarily needed
- protect the deployment with HTTPS
- restrict access by firewall, VPN, reverse proxy, or IP allowlist where possible
- store `.env` securely
- use strong and unique database credentials
- use a dedicated database account with minimal privileges
- keep `Options -MultiViews` in place
- verify that direct access to `src/`, `vendor/`, and `docs/` is denied
- monitor logs for repeated failures or unauthorized requests

---

## Disclosure Process

When a valid security issue is reported, the maintainer should aim to:

1. acknowledge receipt
2. reproduce and validate the issue
3. assess impact and severity
4. prepare a fix or mitigation
5. release an update
6. publish an appropriate security note if needed

Response times may vary depending on project availability, but reports should be handled responsibly and confidentially.

---

## Final Note

Security is not only about code. Safe deployment, careful configuration, HTTPS, limited permissions, and operational discipline are all part of running this service securely.
