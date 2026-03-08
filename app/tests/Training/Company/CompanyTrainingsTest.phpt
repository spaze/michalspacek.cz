<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Company;

use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Training\Exceptions\CompanyTrainingDoesNotExistException;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class CompanyTrainingsTest extends TestCase
{

	public function __construct(
		private readonly CompanyTrainings $companyTrainings,
		private readonly Database $database,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testGetInfoNotExists(): void
	{
		Assert::exception(function (): void {
			$this->companyTrainings->getInfo('foo');
		}, CompanyTrainingDoesNotExistException::class, "Company training 'foo' doesn't exist");
	}


	public function testGetInfo(): void
	{
		$this->database->setFetchDefaultResult([
			'id' => 123,
			'action' => 'action',
			'name' => 'companyTraining',
			'description' => 'companyTrainingDescription',
			'content' => 'content',
			'upsell' => 'upsell',
			'prerequisites' => null,
			'audience' => null,
			'capacity' => null,
			'price' => 1337,
			'alternativeDurationPrice' => 440,
			'studentDiscount' => null,
			'materials' => null,
			'custom' => 0,
			'duration' => 'duration',
			'alternativeDuration' => 'alternativeDuration',
			'alternativeDurationPriceText' => 'alternativeDurationPriceText',
			'successorId' => null,
			'discontinuedId' => null,
		]);
		$training = $this->companyTrainings->getInfo('foo comp');
		Assert::same(123, $training->getId());
		Assert::same('action', $training->getAction());
		Assert::same('companyTraining', $training->getName()->render());
		Assert::same('companyTrainingDescription', $training->getDescription()->render());
		Assert::same('content', $training->getContent()->render());
		Assert::same('upsell', $training->getUpsell()->render());
		Assert::null($training->getPrerequisites());
		Assert::null($training->getAudience());
		Assert::null($training->getCapacity());
		Assert::same(1337, $training->getPrice());
		Assert::same(440, $training->getAlternativeDurationPrice());
		Assert::null($training->getStudentDiscount());
		Assert::null($training->getMaterials());
		Assert::false($training->isCustom());
		Assert::same('duration', $training->getDuration()->render());
		Assert::same('alternativeDuration', $training->getAlternativeDuration()->render());
		Assert::same('alternativeDurationPriceText', $training->getAlternativeDurationPriceText()->render());
		Assert::null($training->getSuccessorId());
		Assert::null($training->getDiscontinuedId());
	}

}

TestCaseRunner::run(CompanyTrainingsTest::class);
