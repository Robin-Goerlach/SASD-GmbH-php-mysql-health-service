# PHP RESTful API Health Service

Ein kleiner, sicher gehaltener Health-Service für PHP mit MySQL-Live-Check.

## Funktionen

- `GET /api/health`  
  Prüft, ob die Datenbankverbindung grundsätzlich funktioniert.
- `GET /api/health/time`  
  Liefert die aktuelle Uhrzeit direkt von der Datenbank.
- `GET /api/phpinfo`  
  Optionaler, standardmäßig deaktivierter `phpinfo()`-Endpunkt.

## Sicherheitsprinzip

Nach außen werden absichtlich keine sensiblen Verbindungsdaten, keine DSNs, keine SQL-Fehler und keine Stacktraces ausgegeben. Bei Problemen liefert der Service nur eine generische Antwort.

Der `phpinfo()`-Endpunkt ist:

- standardmäßig **deaktiviert**,
- nur per `.env` aktivierbar,
- zusätzlich über ein Token geschützt.

## Voraussetzungen

- PHP 8.1 oder neuer
- PDO
- `pdo_mysql`
- Webserver mit Document Root auf `public/`

## Installation

### 1. Projekt entpacken

### 2. Composer Autoload-Datei erzeugen

```bash
composer install
```

### 3. `.env` anlegen

```bash
cp .env.example .env
```

Danach Werte in `.env` anpassen.

## Entwicklung mit eingebautem PHP-Server

```bash
php -S 127.0.0.1:8080 -t public public/index.php
```

## Endpunkte

### Health Check

```http
GET /api/health
```

Beispielantwort:

```json
{
  "status": "ok",
  "database": "ok"
}
```

### Datenbankzeit

```http
GET /api/health/time
```

Beispielantwort:

```json
{
  "status": "ok",
  "database": "ok",
  "db_time": "24.03.2026:16:42"
}
```

### phpinfo

Nur wenn in `.env` aktiviert:

```env
APP_PHPINFO_ENABLED=true
APP_PHPINFO_TOKEN=ein-langes-zufaelliges-token
```

Aufruf dann mit Header:

```http
GET /api/phpinfo
X-Health-Token: ein-langes-zufaelliges-token
```

Alternativ mit Query-Parameter:

```http
GET /api/phpinfo?token=ein-langes-zufaelliges-token
```

## Apache-Hinweis

Der Document Root sollte auf `public/` zeigen. Eine kleine `.htaccess` liegt bei.

## Wichtige Empfehlung

Lege die `.env` außerhalb des öffentlich erreichbaren Webroots ab oder sorge per Serverkonfiguration dafür, dass sie nie ausgeliefert werden kann.
