#!/usr/bin/env php
<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Bin;

use MichalSpacekCz\Application\Bootstrap;
use MichalSpacekCz\Application\Cli\NoCliArgs;
use MichalSpacekCz\Makefile\Exceptions\MakefileException;
use MichalSpacekCz\Makefile\Makefile;

require __DIR__ . '/../vendor/autoload.php';

$makefile = Bootstrap::bootCli(NoCliArgs::class)->getByType(Makefile::class);
try {
	$makefile->checkAllTargetsArePhony(__DIR__ . '/../Makefile');
	echo "[Cajk] All Makefile targets are phony\n";
	exit(0);
} catch (MakefileException $e) {
	echo "[Error] {$e->getMessage()}\n";
	exit(1);
}
