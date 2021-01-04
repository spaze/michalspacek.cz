<?php
declare(strict_types = 1);

use MichalSpacekCz\Application\Bootstrap;

if (file_exists('./maintenance.php')) {
	require 'maintenance.php';
}

$siteDir = realpath(__DIR__ . '/../..');
require $siteDir . '/vendor/autoload.php';
require $siteDir . '/app/Application/Bootstrap.php';

$logDir = $siteDir . '/log';
$tempDir = $siteDir . '/temp';
$environment = (isset($_SERVER['ENVIRONMENT']) ? $_SERVER['ENVIRONMENT'] : null);

$bootstrap = new Bootstrap($siteDir, $logDir, $tempDir, $environment, 'Europe/Prague');
$bootstrap->run();
