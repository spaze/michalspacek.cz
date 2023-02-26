<?php
declare(strict_types = 1);

# Used by efabrica/phpstan-latte – PHPStan Latte extension

use MichalSpacekCz\Application\Bootstrap;
use Nette\Application\IPresenterFactory;

return Bootstrap::bootCli()->getByType(IPresenterFactory::class);
