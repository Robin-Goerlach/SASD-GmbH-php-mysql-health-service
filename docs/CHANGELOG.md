# CHANGELOG.md

All notable changes to this project should be documented in this file.

This changelog follows the spirit of **Keep a Changelog** and uses **Semantic Versioning** where practical.

---

## [Unreleased]

### Added

- Placeholder section for upcoming changes.

---

## [0.1.0] - 2026-03-24

### Added

- Initial RESTful PHP health service for MySQL.
- Basic live database connectivity endpoint.
- Database time endpoint returning the current time from MySQL.
- Optional token-protected `phpinfo()` endpoint.
- Minimal routing entry point through `public/index.php`.
- Lightweight `.env` loading without exposing sensitive values in responses.
- English project documentation.
- Administration guide.
- Developer guide.
- API reference.
- Security policy.
- Initial EditorConfig support.

### Security

- Generic external error responses to reduce information disclosure.
- `phpinfo()` designed to be disabled by default.
- `.env` intended to remain outside version control and protected from public access.

### Notes

- This is the first public baseline release of the service.
