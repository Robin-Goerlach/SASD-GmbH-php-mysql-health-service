<?php
declare(strict_types=1);

namespace Sasd\HealthService\Controller;

use Sasd\HealthService\Infrastructure\Database\DatabaseConnectionFactory;
use Sasd\HealthService\Support\JsonResponse;
use Throwable;

final class HealthController
{
    public function status(): void
    {
        try {
            $pdo = DatabaseConnectionFactory::create();
            $pdo->query('SELECT 1');

            JsonResponse::send(200, [
                'status' => 'ok',
                'database' => 'ok',
            ]);
            return;
        } catch (Throwable) {
            error_log('[health-service] Database status check failed.');

            JsonResponse::send(503, [
                'status' => 'error',
            ]);
            return;
        }
    }

    public function time(): void
    {
        try {
            $pdo = DatabaseConnectionFactory::create();
            $statement = $pdo->query("SELECT DATE_FORMAT(NOW(), '%d.%m.%Y:%H:%i') AS db_time");
            $row = $statement->fetch();

            JsonResponse::send(200, [
                'status' => 'ok',
                'database' => 'ok',
                'db_time' => $row['db_time'] ?? null,
            ]);
            return;
        } catch (Throwable) {
            error_log('[health-service] Database time check failed.');

            JsonResponse::send(503, [
                'status' => 'error',
            ]);
            return;
        }
    }
}
