<?php
declare(strict_types=1);

namespace Sasd\HealthService;

use Sasd\HealthService\Controller\HealthController;
use Sasd\HealthService\Controller\PhpInfoController;
use Sasd\HealthService\Support\Env;
use Sasd\HealthService\Support\JsonResponse;

final class Bootstrap
{
    public static function run(string $projectRoot): void
    {
        Env::load($projectRoot . '/.env');

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($requestUri, PHP_URL_PATH) ?: '/';

        // Optionales Präfix entfernen, falls Anwendung in Unterverzeichnis liegt
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        if ($baseDir !== '' && $baseDir !== '.' && str_starts_with($path, $baseDir)) {
            $path = substr($path, strlen($baseDir)) ?: '/';
        }

        $healthController = new HealthController();
        $phpInfoController = new PhpInfoController();

        if ($method === 'GET' && $path === '/api/health') {
            $healthController->status();
            return;
        }

        if ($method === 'GET' && $path === '/api/health/time') {
            $healthController->time();
            return;
        }

        if ($method === 'GET' && $path === '/api/phpinfo') {
            $phpInfoController->show();
            return;
        }

        JsonResponse::send(404, [
            'status' => 'error',
            'message' => 'Not Found',
        ]);
    }
}
