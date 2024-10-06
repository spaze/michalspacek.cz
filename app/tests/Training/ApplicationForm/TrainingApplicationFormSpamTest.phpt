<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training\ApplicationForm;

use Generator;
use MichalSpacekCz\Test\NullLogger;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Training\Exceptions\SpammyApplicationException;
use stdClass;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class TrainingApplicationFormSpamTest extends TestCase
{

	public function __construct(
		private readonly NullLogger $nullLogger,
		private readonly TrainingApplicationFormSpam $formSpam,
	) {
	}


	public function getValues(): Generator
	{
		$values = new stdClass();
		$values->note = 'foo href="https:// example" bar baz';
		yield [$values, false];

		$values = new stdClass();
		$values->name = 'zggnbijhah';
		$values->companyId = 'vwetyeofcx';
		$values->companyTaxId = 'tyqvukaims';
		$values->company = 'qzpormrfcq';
		yield [$values, false];

		$values = new stdClass();
		$values->name = 'zggnbijhah';
		yield [$values, false];

		yield [new stdClass(), false];
		$values = new stdClass();
		$values->name = 'foo bar';
		yield [$values, true];

		$values = new stdClass();
		$values->companyId = 'foobar1';
		yield [$values, true];

		$values = new stdClass();
		$values->companyTaxId = 'foobar1';
		yield [$values, true];
	}


	/**
	 * @dataProvider getValues
	 */
	public function testIsSpam(stdClass $values, bool $isNice): void
	{
		$check = function () use ($values): void {
			$this->formSpam->check($values);
		};
		if ($isNice) {
			Assert::noError($check);
		} else {
			Assert::exception($check, SpammyApplicationException::class);
		}
		Assert::same([], $this->nullLogger->getLogged());
	}

}

TestCaseRunner::run(TrainingApplicationFormSpamTest::class);
