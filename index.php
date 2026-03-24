<?php
declare(strict_types=1);

use Sasd\HealthService\Bootstrap;

$projectRoot = __DIR__;
$autoload = $projectRoot . '/vendor/autoload.php';

if (is_file($autoload)) {
    require_once $autoload;
} else {
    require_once $projectRoot . '/src/Bootstrap.php';
    require_once $projectRoot . '/src/Support/Env.php';
    require_once $projectRoot . '/src/Support/JsonResponse.php';
    require_once $projectRoot . '/src/Infrastructure/Database/DatabaseConnectionFactory.php';
    require_once $projectRoot . '/src/Controller/HealthController.php';
    require_once $projectRoot . '/src/Controller/PhpInfoController.php';
}

Bootstrap::run($projectRoot);
