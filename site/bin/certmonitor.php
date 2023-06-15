#!/usr/bin/env php
<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Bin;

use MichalSpacekCz\Application\Bootstrap;
use MichalSpacekCz\Application\ServerEnv;
use MichalSpacekCz\Tls\CertificateMonitor;
use Nette\Utils\Arrays;

require __DIR__ . '/../vendor/autoload.php';

Bootstrap::bootCli()->getByType(CertificateMonitor::class)->run(!Arrays::contains(ServerEnv::getList('argv'), '--no-ipv6'));
