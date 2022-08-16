#!/usr/bin/env php
<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Bin;

use MichalSpacekCz\Application\Bootstrap;
use MichalSpacekCz\Tls\CertificateMonitor;
use Nette\Utils\Arrays;

require __DIR__ . '/../vendor/autoload.php';

Bootstrap::bootCli()->getByType(CertificateMonitor::class)->run(!Arrays::contains($_SERVER['argv'], '--no-ipv6'));
