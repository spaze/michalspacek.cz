<?php
use \MichalSpacekCz\Application\Bootstrap;

// Uncomment this line if you must temporarily take down your site for maintenance.
// require '.maintenance.php';

$rootDir = realpath(__DIR__ . '/../..');

require $rootDir . '/app/models/Application/Bootstrap.php';

$appDir = $rootDir . '/app';
$logDir = $rootDir . '/log';
$tempDir = $rootDir . '/temp';
$environment = (isset($_SERVER['ENVIRONMENT']) ? $_SERVER['ENVIRONMENT'] : Bootstrap::MODE_PRODUCTION);

$bootstrap = new Bootstrap($appDir, $logDir, $tempDir, $environment);
$bootstrap->run();
