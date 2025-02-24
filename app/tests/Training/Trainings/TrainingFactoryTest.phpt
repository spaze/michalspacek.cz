<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Trainings;

use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Database\Row;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class TrainingFactoryTest extends TestCase
{

	public function __construct(
		private readonly TrainingFactory $trainingFactory,
	) {
	}


	public function testCreateFromDatabaseRowCustom(): void
	{
		$row = new Row();
		$row->id = 1;
		$row->action = 'action';
		$row->name = '**Name**';
		$row->description = '//Description//';
		$row->content = 'Le **content**';
		$row->upsell = null;
		$row->prerequisites = null;
		$row->audience = null;
		$row->capacity = null;
		$row->price = null;
		$row->studentDiscount = null;
		$row->materials = null;
		$row->custom = 1;
		$row->successorId = null;
		$row->discontinuedId = null;

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
		$row = new Row();
		$row->id = 1;
		$row->action = 'action';
		$row->name = '**Name**';
		$row->description = '//Description//';
		$row->content = 'Le **content**';
		$row->upsell = '//Upsell//';
		$row->prerequisites = '//Prerequisites//';
		$row->audience = '//Audience//';
		$row->capacity = 303;
		$row->price = 404;
		$row->studentDiscount = 42;
		$row->materials = '**Mat**//aerials//';
		$row->custom = 0;
		$row->successorId = 808;
		$row->discontinuedId = 909;

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

TestCaseRunner::run(TrainingFactoryTest::class);
