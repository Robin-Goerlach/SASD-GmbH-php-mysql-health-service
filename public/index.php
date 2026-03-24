<?php
declare(strict_types=1);

use Sasd\HealthService\Bootstrap;

require_once dirname(__DIR__) . '/src/Support/Env.php';

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
} else {
    require_once dirname(__DIR__) . '/src/Bootstrap.php';
    require_once dirname(__DIR__) . '/src/Support/JsonResponse.php';
    require_once dirname(__DIR__) . '/src/Infrastructure/Database/DatabaseConnectionFactory.php';
    require_once dirname(__DIR__) . '/src/Controller/HealthController.php';
    require_once dirname(__DIR__) . '/src/Controller/PhpInfoController.php';
}

Bootstrap::run(dirname(__DIR__));
