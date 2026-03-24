# CONTRIBUTING.md

## Contributing

Thank you for your interest in contributing to the **SASD GmbH PHP MySQL Health Service**.

This project is intentionally small, focused, and security-conscious. Contributions are welcome, but changes should preserve that philosophy.

---

## Guiding Principles

When contributing, please keep these goals in mind:

- keep the service minimal
- prefer safe defaults
- avoid unnecessary complexity
- avoid information leakage
- document behavior clearly
- keep the public API stable unless there is a strong reason to change it
- preserve compatibility with the intended `api.sasd.de/health` subfolder deployment model

---

## Before You Start

Please review the existing documentation first:

- `README.md`
- `docs/ADMIN.md`
- `docs/DEVELOPER.md`
- `docs/API_REFERENCE.md`
- `docs/SECURITY.md`
- `docs/TESTING.md`

If your change affects public behavior, configuration, or operational guidance, please update the relevant documentation as part of the same contribution.

---

## Types of Contributions

Helpful contributions include:

- bug fixes
- security improvements
- documentation improvements
- code cleanup that improves clarity without changing behavior
- small test additions
- deployment or hardening guidance improvements

Less helpful contributions for this project are usually:

- turning the service into a large framework
- adding broad diagnostics output
- exposing sensitive internal details
- introducing unnecessary dependencies
- adding features that do not match the health-service scope

---

## Coding Expectations

Please follow these conventions:

- use clear and consistent naming
- keep code easy to read
- favor maintainability over cleverness
- preserve the service's minimal external responses
- do not expose secrets, DSNs, SQL errors, or stack traces
- keep security implications in mind before adding new endpoints or behavior
- keep the subfolder path normalization behavior intact unless there is a strong reason to redesign it

If you introduce a structural change, explain why it is needed and what alternatives were considered.

---

## Commit Messages

Clear commit messages are appreciated. Examples:

- `feat: add token validation tests for phpinfo endpoint`
- `fix: normalize subfolder routes for shared hosting`
- `docs: improve installation and hardening guidance`

---

## Pull Requests

A good pull request should:

- describe the problem being solved
- explain the chosen approach
- mention any security impact
- mention any documentation updates
- stay focused in scope

Small, well-scoped pull requests are preferred over large mixed changes.

---

## Security Issues

Please do **not** report security vulnerabilities through public issues or pull requests.

See `docs/SECURITY.md` for the project's security reporting guidance.

---

## Final Recommendation

This project is meant to stay small and reliable. The best contributions usually improve clarity, safety, consistency, and maintainability without making the service unnecessarily heavy.
