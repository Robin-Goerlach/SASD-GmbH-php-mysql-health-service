# SASD GmbH PHP MySQL Health Service

A minimal, security-focused RESTful PHP health service for MySQL.

It provides a safe live database check, a database time endpoint, and an optional protected `phpinfo()` endpoint without exposing sensitive connection details, SQL errors, DSNs, or internal stack traces.

## Features

- `GET /api/health`  
  Performs a basic live database connectivity check.

- `GET /api/health/time`  
  Returns the current time directly from the database server.

- `GET /api/phpinfo`  
  Optional `phpinfo()` endpoint, disabled by default and protected by token authentication.

## Security Principles

This service is intentionally designed to reveal as little as possible to the outside world.

It does **not** expose:

- database credentials
- DSNs
- SQL error messages
- stack traces
- internal exception details

If something fails, the service returns only a generic error response.

The `phpinfo()` endpoint is:

- disabled by default
- enabled only through `.env`
- additionally protected with a token

## Requirements

- PHP 8.1 or newer
- PDO
- `pdo_mysql`
- A web server with its document root pointing to `public/`

## Installation

### 1. Clone or extract the project

### 2. Install dependencies

```bash
composer install
