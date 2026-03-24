<?php
declare(strict_types=1);

namespace Sasd\HealthService\Controller;

use Sasd\HealthService\Support\Env;

final class PhpInfoController
{
    public function show(): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');

        $enabled = Env::getBool('APP_PHPINFO_ENABLED', false);
        $expectedToken = Env::get('APP_PHPINFO_TOKEN', '');
        $providedToken = $this->extractToken();

        if (!$enabled) {
            http_response_code(404);
            echo 'Not Found';
            return;
        }

        if ($expectedToken === '' || !hash_equals($expectedToken, $providedToken)) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        phpinfo();
    }

    private function extractToken(): string
    {
        $headerToken = $_SERVER['HTTP_X_HEALTH_TOKEN'] ?? '';
        if ($headerToken !== '') {
            return (string) $headerToken;
        }

        return isset($_GET['token']) ? (string) $_GET['token'] : '';
    }
}
