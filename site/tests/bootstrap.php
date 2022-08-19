<?php
declare(strict_types = 1);

use MichalSpacekCz\Application\Bootstrap;
use MichalSpacekCz\Test\TestCaseRunner;

require __DIR__ . '/../vendor/autoload.php';

return Bootstrap::bootTest()->getByType(TestCaseRunner::class);
