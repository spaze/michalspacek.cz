<?php
declare(strict_types = 1);

use MichalSpacekCz\DependencyInjection\DiServices;
use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

// Needed so the analyser can resolve classes from vendor-dev (e.g. Tester\*, JetBrains\*)
// Remove once https://github.com/shipmonk-rnd/composer-dependency-analyser/issues/258 is implemented
$vendorDevAutoload = __DIR__ . '/vendor-dev/vendor/autoload.php';
if (!is_file($vendorDevAutoload)) {
	throw new RuntimeException('Missing development autoloader at "' . $vendorDevAutoload . '", run "composer --working-dir=vendor-dev install" and try again.');
}
require $vendorDevAutoload;

return (new Configuration())
	->setFileExtensions(['php', 'phpt'])
	->addPathToScan(__DIR__ . '/tests', true)

	// Add classes from services.neon and extensions.neon
	->addForceUsedSymbols(DiServices::getAllClasses())

	// It's used, believe me
	->ignoreErrorsOnPackage('async-aws/lambda', [ErrorType::UNUSED_DEPENDENCY])
	->ignoreErrorsOnPackage('mlocati/ip-lib', [ErrorType::UNUSED_DEPENDENCY])

	// These are in vendor-dev/composer.json, not in the main composer.json
	// Remove once https://github.com/shipmonk-rnd/composer-dependency-analyser/issues/258 is implemented
	->ignoreErrorsOnPackages([
		'jetbrains/phpstorm-attributes',
		'nette/component-model',
		'nette/tester',
	], [ErrorType::SHADOW_DEPENDENCY])
	->ignoreErrorsOnExtensions([
		'ext-simplexml',
	], [ErrorType::SHADOW_DEPENDENCY])

	->ignoreErrorsOnExtensions([
		'ext-gd', // Used by e.g. Nette\Http\FileUpload::toImage which is used by MichalSpacekCz\Media\VideoThumbnails::validateUpload()
		'ext-pcntl', // Used by latte/latte Latte\Tools\Linter
	], [ErrorType::UNUSED_DEPENDENCY])
;
