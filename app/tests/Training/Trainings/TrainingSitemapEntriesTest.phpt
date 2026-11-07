<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Trainings;

use MichalSpacekCz\Sitemap\SitemapEntry;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class TrainingSitemapEntriesTest extends TestCase
{

	public function testGetEntries(): void
	{
		$entries = new TrainingSitemapEntries(new SitemapTrainings([
			1 => ['cs_CZ' => ['name' => 'skoleni'], 'en_US' => ['name' => 'training']],
			2 => ['cs_CZ' => ['name' => 'jen-cesky']],
		]));
		Assert::equal([
			new SitemapEntry(['cs_CZ' => ['name' => 'skoleni'], 'en_US' => ['name' => 'training']]),
			new SitemapEntry(['cs_CZ' => ['name' => 'jen-cesky']]),
		], $entries->getEntries());
	}

}

TestCaseRunner::run(TrainingSitemapEntriesTest::class);
