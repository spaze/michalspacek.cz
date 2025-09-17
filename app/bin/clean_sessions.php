#!/usr/bin/env php
<?php
declare(strict_types = 1);

/**
 * Deletes stale session database rows.
 * Run daily or so, depends on the session expiry times, using cron or systemd timers.
 */

namespace MichalSpacekCz\Bin;

use MichalSpacekCz\Application\Bootstrap;
use MichalSpacekCz\Application\Cli\NoCliArgs;
use Spaze\Session\MysqlSessionHandler;
use Throwable;
use Tracy\Debugger;

require __DIR__ . '/../vendor/autoload.php';

try {
	$sessionHandler = Bootstrap::bootCli(NoCliArgs::class)->getByType(MysqlSessionHandler::class);
	$rows = $sessionHandler->gc(24 * 60 * 60);
	if ($rows === false) {
		Debugger::log(sprintf('Something went wrong, %s::gc() returned false', $sessionHandler::class), Debugger::ERROR);
		exit(1);
	}
	exit(0);
} catch (Throwable $e) {
	Debugger::log($e);
	exit(2);
}
