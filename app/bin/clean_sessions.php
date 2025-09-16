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

require __DIR__ . '/../vendor/autoload.php';

$sessionHandler = Bootstrap::bootCli(NoCliArgs::class)->getByType(MysqlSessionHandler::class);
$rows = $sessionHandler->gc(24 * 60 * 60);
if ($rows === false) {
	echo "Oops, something went wrong\n";
	exit(1);
}
echo "Number of stale sessions deleted: {$rows}\n";
exit(0);
