<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Trainings;

use Nette\Database\Row;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class TrainingFactoryTest extends TestCase
{

	public function __construct(
		private readonly TrainingFactory $trainingFactory,
	) {
	}


	public function testCreateFromDatabaseRowCustom(): void
	{
		$row = Row::from([
			'id' => 1,
			'action' => 'action',
			'name' => '**Name**',
			'description' => '//Description//',
			'content' => 'Le **content**',
			'upsell' => null,
			'prerequisites' => null,
			'audience' => null,
			'capacity' => null,
			'price' => null,
			'studentDiscount' => null,
			'materials' => null,
			'custom' => 1,
			'successorId' => null,
			'discontinuedId' => null,
		]);
		$training = $this->trainingFactory->createFromDatabaseRow($row);
		Assert::same(1, $training->getId());
		Assert::same('action', $training->getAction());
		Assert::same('<strong>Name</strong>', $training->getName()->render());
		Assert::same('<em>Description</em>', $training->getDescription()->render());
		Assert::same('Le <strong>content</strong>', $training->getContent()->render());
		Assert::null($training->getUpsell());
		Assert::null($training->getPrerequisites());
		Assert::null($training->getAudience());
		Assert::null($training->getCapacity());
		Assert::null($training->getPrice());
		Assert::null($training->getStudentDiscount());
		Assert::null($training->getMaterials());
		Assert::true($training->isCustom());
		Assert::null($training->getSuccessorId());
		Assert::null($training->getDiscontinuedId());
	}


	public function testCreateFromDatabaseRow(): void
	{
		$row = Row::from([
			'id' => 1,
			'action' => 'action',
			'name' => '**Name**',
			'description' => '//Description//',
			'content' => 'Le **content**',
			'upsell' => '//Upsell//',
			'prerequisites' => '//Prerequisites//',
			'audience' => '//Audience//',
			'capacity' => 303,
			'price' => 404,
			'studentDiscount' => 42,
			'materials' => '**Mat**//aerials//',
			'custom' => 0,
			'successorId' => 808,
			'discontinuedId' => 909,
		]);
		$training = $this->trainingFactory->createFromDatabaseRow($row);
		Assert::same(1, $training->getId());
		Assert::same('action', $training->getAction());
		Assert::same('<strong>Name</strong>', $training->getName()->render());
		Assert::same('<em>Description</em>', $training->getDescription()->render());
		Assert::same('Le <strong>content</strong>', $training->getContent()->render());
		Assert::same('<em>Upsell</em>', $training->getUpsell()?->render());
		Assert::same('<em>Prerequisites</em>', $training->getPrerequisites()?->render());
		Assert::same('<em>Audience</em>', $training->getAudience()?->render());
		Assert::same(303, $training->getCapacity());
		Assert::same(404, $training->getPrice());
		Assert::same(42, $training->getStudentDiscount());
		Assert::same('<strong>Mat</strong><em>aerials</em>', $training->getMaterials()?->render());
		Assert::false($training->isCustom());
		Assert::same(808, $training->getSuccessorId());
		Assert::same(909, $training->getDiscontinuedId());
	}

}

$runner->run(TrainingFactoryTest::class);
