<?php
declare(strict_types=1);

namespace Sasd\HealthService\Infrastructure\Database;

use PDO;
use RuntimeException;
use Sasd\HealthService\Support\Env;

final class DatabaseConnectionFactory
{
    public static function create(): PDO
    {
        $driver = Env::get('DB_DRIVER', 'mysql');

        if ($driver !== 'mysql') {
            throw new RuntimeException('Unsupported database driver.');
        }

        $host = Env::get('DB_HOST', '127.0.0.1');
        $port = Env::get('DB_PORT', '3306');
        $dbName = Env::get('DB_NAME');
        $user = Env::get('DB_USER');
        $pass = Env::get('DB_PASS', '');
        $charset = Env::get('DB_CHARSET', 'utf8mb4');

        if ($dbName === null || $user === null) {
            throw new RuntimeException('Database configuration incomplete.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $host,
            $port,
            $dbName,
            $charset
        );

        return new PDO(
            $dsn,
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }
}
