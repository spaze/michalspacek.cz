#!/usr/bin/env php
<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Bin;

use MichalSpacekCz\Application\Bootstrap;
use MichalSpacekCz\Templating\LatteLinter;

require __DIR__ . '/../vendor/autoload.php';

Bootstrap::bootCli(LatteLinter::class)->getByType(LatteLinter::class)->scan();
