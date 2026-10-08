<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class DependencyVersionTest extends TestCase
{

	public function testGetters(): void
	{
		$version = new DependencyVersion('1.2.3', 'c4f37ea5');
		Assert::same('1.2.3', $version->getVersion());
		Assert::same('c4f37ea5', $version->getReference());
		Assert::same('1.2.3@c4f37ea5', $version->getFullVersion());
		Assert::same('<code>1.2.3<small>@c4f37ea5</small></code>', $version->getFullVersionHtml()->render());
	}


	public function testEquals(): void
	{
		$version = new DependencyVersion('1.2.3', 'c4f37ea5');
		$versionSame = new DependencyVersion('1.2.3', 'c4f37ea5');
		$versionDifferent = new DependencyVersion('1.2.4', '7ea5c4f3');
		Assert::true($version->equals($versionSame));
		Assert::false($version->equals($versionDifferent));
	}

}

TestCaseRunner::run(DependencyVersionTest::class);
