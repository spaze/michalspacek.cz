<?php
declare(strict_types = 1);

# Used by efabrica/phpstan-latte – PHPStan Latte extension

use MichalSpacekCz\Application\Bootstrap;
use MichalSpacekCz\Application\Cli\NoCliArgs;
use Nette\Application\IPresenterFactory;

return Bootstrap::bootCli(NoCliArgs::class)->getByType(IPresenterFactory::class);
