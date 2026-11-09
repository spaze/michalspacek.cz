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


	public function testGetLocaleActions(): void
	{
		$this->database->setFetchAllDefaultResult([
			['trainingId' => 1, 'language' => 'cs_CZ', 'action' => 'skoleni-php'],
			['trainingId' => 1, 'language' => 'en_US', 'action' => 'php'],
		]);
		Assert::same(['cs_CZ' => 'skoleni-php', 'en_US' => 'php'], $this->trainingLocales->getLocaleActions('php', 'en_US'));
		Assert::same(['php', 'en_US'], $this->database->getParamsForQueryContaining('ORDER BY l.language = ?'), 'The query picks the training with the URL in the locale');
	}


	public function testGetLocaleActionsNoTraining(): void
	{
		Assert::same([], $this->trainingLocales->getLocaleActions('foo', 'cs_CZ'));
	}


	public function testGetLocaleLinkParams(): void
	{
		Assert::same(['*' => ['param' => 'value']], $this->trainingLocales->getLocaleLinkParams(null, ['param' => 'value']));

		$this->database->setFetchAllDefaultResult([
			['trainingId' => 2, 'language' => 'cs_CZ', 'action' => 'foo'],
		]);
		Assert::same(['cs_CZ' => ['name' => 'foo']], $this->trainingLocales->getLocaleLinkParams('foo', ['param' => 'value']));
		Assert::same(['foo', 'cs_CZ'], $this->database->getParamsForQueryContaining('ORDER BY l.language = ?'), 'The training with the URL in the locale of the page');
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
