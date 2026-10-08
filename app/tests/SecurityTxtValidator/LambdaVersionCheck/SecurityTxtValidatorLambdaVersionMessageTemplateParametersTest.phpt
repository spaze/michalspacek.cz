<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\LambdaVersionCheck;

use MichalSpacekCz\Application\DependencyVersion;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class SecurityTxtValidatorLambdaVersionMessageTemplateParametersTest extends TestCase
{

	public function testStoresProperties(): void
	{
		$version = new DependencyVersion('1.2.3', 'c4f37ea5');
		$params = new SecurityTxtValidatorLambdaVersionMessageTemplateParameters($version, $version, 'pack/age');
		Assert::same($version, $params->lambdaVersion);
		Assert::same($version, $params->installedVersion);
		Assert::same('pack/age', $params->package);
	}

}

TestCaseRunner::run(SecurityTxtValidatorLambdaVersionMessageTemplateParametersTest::class);
