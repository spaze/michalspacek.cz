#!/usr/bin/env php
<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Bin;

use MichalSpacekCz\Application\Bootstrap;
use MichalSpacekCz\Tls\CertificateMonitor;
use Nette\Utils\Arrays;

require __DIR__ . '/../vendor/autoload.php';

$siteDir = realpath(__DIR__ . '/..');
require $siteDir . '/vendor/autoload.php';

$bootstrap = new Bootstrap($siteDir);
/** @var CertificateMonitor $certificateMonitor */
$certificateMonitor = $bootstrap->bootCli()->getByType(CertificateMonitor::class);
$certificateMonitor->run(!Arrays::contains($_SERVER['argv'], '--no-ipv6'));
