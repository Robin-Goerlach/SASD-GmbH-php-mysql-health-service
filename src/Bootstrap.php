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

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = self::detectPath();

        $healthController = new HealthController();
        $phpInfoController = new PhpInfoController();

        if (($method === 'GET' || $method === 'HEAD') && ($path === '/' || $path === '')) {
            $healthController->status();
            return;
        }

        if (($method === 'GET' || $method === 'HEAD') && $path === '/time') {
            $healthController->time();
            return;
        }

        if ($method === 'GET' && $path === '/phpinfo') {
            $phpInfoController->show();
            return;
        }

        JsonResponse::send(404, [
            'status' => 'error',
            'message' => 'Not Found',
        ]);
    }

    private static function detectPath(): string
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($requestUri, PHP_URL_PATH) ?: '/';

        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

        if ($baseDir !== '' && $baseDir !== '.' && str_starts_with($path, $baseDir)) {
            $path = substr($path, strlen($baseDir)) ?: '/';
        }

        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
            if ($path === '') {
                $path = '/';
            }
        }

        return $path;
    }
}
