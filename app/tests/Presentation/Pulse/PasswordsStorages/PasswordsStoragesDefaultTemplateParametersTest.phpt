<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Pulse\PasswordsStorages;

use MichalSpacekCz\Pulse\Passwords\Storage\StorageRegistry;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class PasswordsStoragesDefaultTemplateParametersTest extends TestCase
{

	public function testStoresProperties(): void
	{
		$registry = new StorageRegistry();
		$ratingGuide = ['A' => 'Best', 'F' => 'Worst'];
		$params = new PasswordsStoragesDefaultTemplateParameters(true, 'Title', $registry, $ratingGuide, false, 'https://example.com/canonical');
		Assert::true($params->isDetail);
		Assert::same('Title', $params->pageTitle);
		Assert::same($registry, $params->data);
		Assert::same($ratingGuide, $params->ratingGuide);
		Assert::false($params->openSearchSort);
		Assert::same('https://example.com/canonical', $params->canonicalLink);
	}

}

TestCaseRunner::run(PasswordsStoragesDefaultTemplateParametersTest::class);
