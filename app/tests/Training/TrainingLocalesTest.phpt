<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class TrainingLocalesTest extends TestCase
{

	public function __construct(
		private readonly TrainingLocales $trainingLocales,
		private readonly Database $database,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testGetLocaleLinkParams(): void
	{
		Assert::same(['*' => ['param' => 'value']], $this->trainingLocales->getLocaleLinkParams(null, ['param' => 'value']));

		$this->database->setFetchPairsDefaultResult([
			'cs_CZ' => 'fů',
			'en_US' => 'foo',
		]);
		$expected = [
			'cs_CZ' => ['name' => 'fů'],
			'en_US' => ['name' => 'foo'],
		];
		Assert::same($expected, $this->trainingLocales->getLocaleLinkParams('foo', ['param' => 'value']));
	}


	public function testGetAllLocaleLinkParams(): void
	{
		$this->database->setFetchAllDefaultResult([
			['trainingId' => 1, 'language' => 'cs_CZ', 'action' => 'skoleni'],
			['trainingId' => 2, 'language' => 'cs_CZ', 'action' => 'jen-cesky'],
			['trainingId' => 1, 'language' => 'en_US', 'action' => 'training'],
		]);
		$expected = [
			1 => ['cs_CZ' => ['name' => 'skoleni'], 'en_US' => ['name' => 'training']],
			2 => ['cs_CZ' => ['name' => 'jen-cesky']],
		];
		Assert::same($expected, $this->trainingLocales->getAllLocaleLinkParams());
	}

}

TestCaseRunner::run(TrainingLocalesTest::class);
