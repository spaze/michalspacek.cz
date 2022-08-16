<?php
declare(strict_types = 1);

use MichalSpacekCz\Application\Bootstrap;
use MichalSpacekCz\Application\WebApplication;

if (file_exists('./maintenance.php')) {
	require 'maintenance.php';
}

require __DIR__ . '/../../vendor/autoload.php';

Bootstrap::boot()->getByType(WebApplication::class)->run();
