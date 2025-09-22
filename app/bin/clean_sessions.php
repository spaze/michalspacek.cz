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
use MichalSpacekCz\Http\Session\SessionGarbageCollector;

require __DIR__ . '/../vendor/autoload.php';

$gc = Bootstrap::bootCli(NoCliArgs::class)->getByType(SessionGarbageCollector::class);
$code = $gc->cleanSessions();
exit($code->value);
