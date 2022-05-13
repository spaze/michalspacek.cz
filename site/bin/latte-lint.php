#!/usr/bin/env php
<?php
declare(strict_types = 1);

/**
 * Almost the same as vendor/latte/latte/bin/latte-lint, but extended
 * to support custom filters by passing a configured engine to the Linter.
 * @see https://github.com/nette/latte/issues/286
 */

namespace MichalSpacekCz\Bin;

use Latte\Tools\Linter;
use MichalSpacekCz\Application\Bootstrap;
use MichalSpacekCz\Templating\TemplateFactory;

require __DIR__ . '/../vendor/autoload.php';

pcntl_signal(SIGINT, function (): void {
	pcntl_signal(SIGINT, SIG_DFL);
	echo "Terminated\n";
	exit(1);
});

$bootstrap = new Bootstrap(__DIR__ . '/..');
$factory = $bootstrap->bootCli()->getByType(TemplateFactory::class);
assert($factory instanceof TemplateFactory);

echo '
Latte linter
------------
';
$customFilters = $factory->getCustomFilters();
echo 'Custom filters: ' . ($customFilters ? implode(', ', $customFilters) : 'none installed') . "\n";

if ($argc < 2) {
	echo "Usage: latte-lint <path>\n";
	exit(1);
}

$debug = in_array('--debug', $argv, true);
$path = $argv[1];
$linter = new Linter($factory->createTemplate()->getLatte(), $debug);
$ok = $linter->scanDirectory($path);
exit($ok ? 0 : 1);
