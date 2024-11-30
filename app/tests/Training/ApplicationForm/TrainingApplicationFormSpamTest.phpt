<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training\ApplicationForm;

use MichalSpacekCz\Test\NullLogger;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Training\Exceptions\SpammyApplicationException;
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


	/**
	 * @return list<array{name:string, companyId:string|null, companyTaxId:string|null, company:string|null, note:string|null, isNice:bool}>
	 */
	public function getValues(): array
	{
		return [
			[
				'name' => 'foo bar',
				'companyId' => null,
				'companyTaxId' => null,
				'company' => null,
				'note' => 'foo href="https:// example" bar baz',
				'isNice' => false,
			],
			[
				'name' => 'zggnbijhah',
				'companyId' => 'vwetyeofcx',
				'companyTaxId' => 'tyqvukaims',
				'company' => 'qzpormrfcq',
				'note' => null,
				'isNice' => false,
			],
			[
				'name' => 'zggnbijhah',
				'companyId' => null,
				'companyTaxId' => null,
				'company' => null,
				'note' => null,
				'isNice' => false,
			],
			[
				'name' => 'foo bar',
				'companyId' => null,
				'companyTaxId' => null,
				'company' => null,
				'note' => null,
				'isNice' => true,
			],
			[
				'name' => '',
				'companyId' => 'foobar1',
				'companyTaxId' => null,
				'company' => null,
				'note' => null,
				'isNice' => true,
			],
			[
				'name' => '',
				'companyId' => null,
				'companyTaxId' => 'foobar1',
				'company' => null,
				'note' => null,
				'isNice' => true,
			],
			[
				'name' => '',
				'companyId' => null,
				'companyTaxId' => null,
				'company' => 'comp any',
				'note' => null,
				'isNice' => true,
			],
		];
	}


	/**
	 * @dataProvider getValues
	 */
	public function testIsSpam(string $name, ?string $companyId, ?string $companyTaxId, ?string $company, ?string $note, bool $isNice): void
	{
		$check = function () use ($name, $company, $companyId, $companyTaxId, $note): void {
			$this->formSpam->check($name, $company, $companyId, $companyTaxId, $note);
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
