<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Discontinued;

use MichalSpacekCz\Test\Database\Database;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class DiscontinuedTrainingsTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly DiscontinuedTrainings $discontinuedTrainings,
	) {
	}


	public function testGetAllDiscontinued(): void
	{
		$this->database->setFetchAllDefaultResult([
			[
				'id' => 1,
				'description' => 'foo',
				'training' => 'intro',
				'href' => 'https://foo.example',
			],
			[
				'id' => 1,
				'description' => 'foo',
				'training' => 'classes',
				'href' => 'https://foo.example',
			],
			[
				'id' => 2,
				'description' => 'bar',
				'training' => 'web',
				'href' => 'https://bar.example',
			],
		]);
		$discontinued = $this->discontinuedTrainings->getAllDiscontinued();
		Assert::count(2, $discontinued);
		Assert::same('foo', $discontinued[0]->getDescription());
		Assert::same(['intro', 'classes'], $discontinued[0]->getTrainings());
		Assert::same('https://foo.example', $discontinued[0]->getNewHref());
		Assert::same('bar', $discontinued[1]->getDescription());
		Assert::same(['web'], $discontinued[1]->getTrainings());
		Assert::same('https://bar.example', $discontinued[1]->getNewHref());
	}


	public function testGetDiscontinued(): void
	{
		Assert::null($this->discontinuedTrainings->getDiscontinued(404));

		$this->database->setFetchAllDefaultResult([
			[
				'description' => 'foo',
				'training' => 'intro',
				'href' => 'https://foo.example',
			],
			[
				'description' => 'foo',
				'training' => 'classes',
				'href' => 'https://foo.example',
			],
		]);
		$discontinued = $this->discontinuedTrainings->getDiscontinued(302);
		Assert::same('foo', $discontinued?->getDescription());
		Assert::same(['intro', 'classes'], $discontinued?->getTrainings());
		Assert::same('https://foo.example', $discontinued?->getNewHref());
	}

}

$runner->run(DiscontinuedTrainingsTest::class);
