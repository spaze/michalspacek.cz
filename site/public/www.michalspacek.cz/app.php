<?php
declare(strict_types = 1);

use MichalSpacekCz\Application\Bootstrap;
use MichalSpacekCz\Application\WebApplication;

if (file_exists('./maintenance.php')) {
	require 'maintenance.php';
}

$siteDir = realpath(__DIR__ . '/../..');
require $siteDir . '/vendor/autoload.php';

$bootstrap = new Bootstrap($siteDir);
$bootstrap->boot()->getByType(WebApplication::class)->run();
