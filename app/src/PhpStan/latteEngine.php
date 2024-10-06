<?php
declare(strict_types = 1);

# Used by efabrica/phpstan-latte – PHPStan Latte extension

use MichalSpacekCz\Application\Bootstrap;
use MichalSpacekCz\Application\Cli\NoCliArgs;
use MichalSpacekCz\Templating\TemplateFactory;

return Bootstrap::bootCli(NoCliArgs::class)->getByType(TemplateFactory::class)->createTemplate()->getLatte();
