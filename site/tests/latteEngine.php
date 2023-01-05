<?php
declare(strict_types = 1);

# Used by efabrica/phpstan-latte – PHPStan Latte extension

use MichalSpacekCz\Application\Bootstrap;
use MichalSpacekCz\Templating\TemplateFactory;

return Bootstrap::bootCli()->getByType(TemplateFactory::class)->createTemplate()->getLatte();
