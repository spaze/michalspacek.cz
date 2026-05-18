#!/usr/bin/env php
<?php
declare(strict_types = 1);

/*
 * Runs all registered garbage collectors.
 *
 * - Run daily via cron or a systemd timer
 * - Execute as the web app user, e.g.: `sudo runuser --user www-data /path/to/collect_garbage.php`
 */

namespace MichalSpacekCz\Bin;

use MichalSpacekCz\Application\Bootstrap;
use MichalSpacekCz\Application\Cli\NoCliArgs;
use MichalSpacekCz\GarbageCollector\GarbageCollectorRunner;

require __DIR__ . '/../vendor/autoload.php';

$runner = Bootstrap::bootCli(NoCliArgs::class)->getByType(GarbageCollectorRunner::class);
exit($runner->run());
