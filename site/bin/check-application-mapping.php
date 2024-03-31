#!/usr/bin/env php
<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Bin;

use MichalSpacekCz\Application\Bootstrap;
use MichalSpacekCz\Application\Cli\NoCliArgs;
use MichalSpacekCz\Application\MappingCheck\ApplicationMappingCheck;
use MichalSpacekCz\Application\MappingCheck\Exceptions\ApplicationMappingException;
use MichalSpacekCz\Makefile\Exceptions\MakefileException;
use MichalSpacekCz\Makefile\Makefile;

require __DIR__ . '/../vendor/autoload.php';

$check = Bootstrap::bootCli(NoCliArgs::class)->getByType(ApplicationMappingCheck::class);
try {
	$files = $check->checkFiles();
	echo "[Cajk] All application mapping configs are the same\n";
	foreach ($files as $file) {
		echo "[Cajk] Checked {$file}\n";
	}
	exit(0);
} catch (ApplicationMappingException $e) {
	echo "[Error] {$e->getMessage()}\n";
	exit(1);
}
