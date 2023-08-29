<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class TrainingLocalesTest extends TestCase
{

	public function __construct(
		private readonly TrainingLocales $trainingLocales,
		private readonly Database $database,
	) {
	}


	public function testGetLocaleLinkParams(): void
	{
		Assert::same(['*' => ['param' => 'value']], $this->trainingLocales->getLocaleLinkParams(null, ['param' => 'value']));

		$this->database->setFetchPairsResult([
			'cs_CZ' => 'fů',
			'en_US' => 'foo',
		]);
		$expected = [
			'cs_CZ' => ['name' => 'fů'],
			'en_US' => ['name' => 'foo'],
		];
		Assert::same($expected, $this->trainingLocales->getLocaleLinkParams('foo', ['param' => 'value']));
	}

}

TestCaseRunner::run(TrainingLocalesTest::class);
