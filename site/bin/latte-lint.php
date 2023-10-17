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
use MichalSpacekCz\Application\Cli\NoCliArgs;
use MichalSpacekCz\Templating\TemplateFactory;

require __DIR__ . '/../vendor/autoload.php';

$factory = Bootstrap::bootCli(NoCliArgs::class)->getByType(TemplateFactory::class);

echo '
Latte linter
------------
';
$customFilters = $factory->getCustomFilters();
echo 'Custom filters: ' . ($customFilters ? implode(', ', $customFilters) : 'none installed') . "\n";

if ($argc < 2) {
	echo "Usage: latte-lint <path> [--debug] [--disable-strict-parsing]\n";
	exit(1);
}

$debug = in_array('--debug', $argv, true);
if ($debug) {
	echo "Debug mode enabled\n";
}
$strictParsing = !in_array('--disable-strict-parsing', $argv, true);
if ($strictParsing) {
	echo "Strict parsing mode enabled\n";
}
$path = $argv[1];
$latteEngine = $factory->createTemplate()->getLatte();
$latteEngine->setStrictParsing($strictParsing);
$linter = new Linter($latteEngine, $debug);
$ok = $linter->scanDirectory($path);
exit($ok ? 0 : 1);
