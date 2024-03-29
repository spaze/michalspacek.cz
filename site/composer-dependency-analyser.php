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

	// TestCaseRunner is used only in tests
	->ignoreErrorsOnPackageAndPath('nette/tester', __DIR__ . '/app/Test/TestCaseRunner.php', [ErrorType::DEV_DEPENDENCY_IN_PROD])
;
