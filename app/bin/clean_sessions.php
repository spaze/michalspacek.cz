#!/usr/bin/env php
<?php
declare(strict_types = 1);

/**
 * Deletes stale session rows from the database.
 *
 * - Run daily or hourly via cron or a systemd timer (adjust the frequency to session expiry)
 * - Execute as the web app user, e.g.: `sudo runuser --user www-data /path/to/clean_sessions.php`
 */

namespace MichalSpacekCz\Bin;

use MichalSpacekCz\Application\Bootstrap;
use MichalSpacekCz\Application\Cli\NoCliArgs;
use MichalSpacekCz\Http\SessionGarbageCollector\SessionGarbageCollector;

require __DIR__ . '/../vendor/autoload.php';

$gc = Bootstrap::bootCli(NoCliArgs::class)->getByType(SessionGarbageCollector::class);
$code = $gc->cleanSessions();
exit($code->value);
