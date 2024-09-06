<?php
declare(strict_types = 1);

use MichalSpacekCz\Application\Bootstrap;
use MichalSpacekCz\Application\WebApplication;

if (file_exists(__DIR__ . '/maintenance.php')) {
	require __DIR__ . '/maintenance.php';
}

require __DIR__ . '/../../vendor/autoload.php';

Bootstrap::boot()->getByType(WebApplication::class)->run();
