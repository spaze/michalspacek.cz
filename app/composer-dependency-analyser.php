<?php
declare(strict_types = 1);

use MichalSpacekCz\DependencyInjection\DiServices;
use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
	// Add classes from services.neon and extensions.neon
	->addForceUsedSymbols(DiServices::getAllClasses())

	// Attributes used for development only
	->ignoreErrorsOnPackage('jetbrains/phpstorm-attributes', [ErrorType::DEV_DEPENDENCY_IN_PROD])

	// It's used, believe me
	->ignoreErrorsOnPackage('latte/latte', [ErrorType::UNUSED_DEPENDENCY])

	->ignoreErrorsOnExtensions([
		'ext-gd', // Used by e.g. Nette\Http\FileUpload::toImage which is used by MichalSpacekCz\Media\VideoThumbnails::validateUpload()
		'ext-pcntl', // Used by latte/latte Latte\Tools\Linter and nette/tester's Tester\Runner\CliTester
		'ext-simplexml', // Used in MichalSpacekCz\Feed\ExportsTest
	], [ErrorType::UNUSED_DEPENDENCY])

	// shipmonk/composer-dependency-analyser#203
	->ignoreErrorsOnExtensionAndPath('ext-session', 'src/EasterEgg/PhpInfoCookieSanitization.php', [ErrorType::SHADOW_DEPENDENCY])

	// TestCaseRunner is used only in tests
	->ignoreErrorsOnPackageAndPath('nette/tester', __DIR__ . '/src/Test/TestCaseRunner.php', [ErrorType::DEV_DEPENDENCY_IN_PROD])
;
