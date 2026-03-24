# SECURITY.md

## Security Policy

Thank you for helping to keep the **SASD GmbH PHP MySQL Health Service** secure.

This project is intentionally small and security-focused. Its purpose is to provide a minimal RESTful health endpoint for MySQL connectivity checks, a database time endpoint, and an optional protected `phpinfo()` endpoint. Because the service may handle credentials and infrastructure-related information, responsible security handling is important.

---

## Supported Versions

Security fixes are normally provided for the latest maintained version of the project.

If multiple tagged releases exist in the future, the maintainers may define a more detailed support matrix. Until then, users should assume that only the current main development line is actively maintained.

| Version | Supported |
| ------- | --------- |
| Latest main branch | Yes |
| Older releases | Best effort |
| Unmaintained forks | No |

---

## Reporting a Vulnerability

Please **do not open a public GitHub issue** for security vulnerabilities.

Instead, report security concerns privately to the maintainer or project owner using a private channel such as email or another agreed confidential contact path.

A good vulnerability report should include:

- a short summary of the issue
- the affected endpoint or component
- the potential impact
- clear reproduction steps
- configuration details if relevant
- suggested mitigation if known

If you are unsure whether something is security-relevant, it is still better to report it privately first.

---

## What to Report

Please report issues such as:

- authentication bypass for the `phpinfo()` endpoint
- accidental disclosure of environment variables or secrets
- SQL error leakage
- stack trace leakage
- path disclosure
- unsafe default configuration
- access control weaknesses
- request handling problems that could expose internal information
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

This service follows a few core security principles:

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
