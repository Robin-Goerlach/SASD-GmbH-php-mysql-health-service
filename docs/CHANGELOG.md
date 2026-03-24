# CHANGELOG.md

All notable changes to this project should be documented in this file.

This changelog follows the spirit of **Keep a Changelog** and uses **Semantic Versioning** where practical.

---

## [Unreleased]

### Changed

- Reworked the project for subfolder deployment under paths such as `/health` on a shared API host.
- Replaced the dedicated `public/` web entry model with a service-root front controller and service-root `.htaccess`.
- Updated routing from `/api/health` style paths to `/health`, `/health/time`, and `/health/phpinfo`.
- Added Apache rules to disable `MultiViews` and deny direct access to internal folders and selected metadata files.
- Updated the documentation set to match the shared-hosting and subfolder deployment model.

---

## [0.1.0] - 2026-03-24

### Added

- Initial RESTful PHP health service for MySQL.
- Basic live database connectivity endpoint.
- Database time endpoint returning the current time from MySQL.
- Optional token-protected `phpinfo()` endpoint.
- Lightweight `.env` loading without exposing sensitive values in responses.
- English project documentation.

### Security

- Generic external error responses to reduce information disclosure.
- `phpinfo()` designed to be disabled by default.
- `.env` intended to remain outside version control and protected from public access.

### Notes

- This was the first baseline release before the later subfolder-deployment adjustments.
