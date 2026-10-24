#!/usr/bin/env php
<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Bin;

use MichalSpacekCz\Application\Bootstrap;
use MichalSpacekCz\Tls\CertificateMonitor;

require __DIR__ . '/../vendor/autoload.php';

exit(Bootstrap::bootCli(CertificateMonitor::class)->getByType(CertificateMonitor::class)->run());
