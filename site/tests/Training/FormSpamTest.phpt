<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use MichalSpacekCz\Test\NullLogger;
use MichalSpacekCz\Training\Exceptions\SpammyApplicationException;
use Nette\Http\SessionSection;
use Nette\Utils\ArrayHash;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class FormSpamTest extends TestCase
{

	private const FORM_NAME = 'formName';


	public function __construct(
		private readonly SessionSection $session,
		private readonly NullLogger $nullLogger,
		private readonly FormSpam $formSpam,
	) {
	}


	protected function tearDown(): void
	{
		$this->nullLogger->reset();
	}


	public function getValues(): array
	{
		return [
			[
				[
					'note' => 'foo href="https:// example" bar baz',
				],
				false,
				'Application session data for ' . self::FORM_NAME . ': empty, form values: note => "foo href="https:// example" bar baz"',
			],
			[
				[
					'name' => 'zggnbijhah',
					'companyId' => 'vwetyeofcx',
					'companyTaxId' => 'tyqvukaims',
					'company' => 'qzpormrfcq',
				],
				false,
				'Application session data for ' . self::FORM_NAME . ': empty, form values: name => "zggnbijhah", companyId => "vwetyeofcx", companyTaxId => "tyqvukaims", company => "qzpormrfcq"',
			],
			[
				[
					'name' => 'zggnbijhah',
				],
				false,
				'Application session data for ' . self::FORM_NAME . ': empty, form values: name => "zggnbijhah"',
			],
			[
				[],
				false,
				'Application session data for ' . self::FORM_NAME . ': empty, form values: empty',
			],
			[
				[
					'name' => 'foo bar',
				],
				true,
				null,
			],
		];
	}


	/**
	 * @dataProvider getValues
	 */
	public function testIsSpam(array $values, bool $isNice, ?string $logged): void
	{
		$check = function () use ($values): void {
			$this->formSpam->check(ArrayHash::from($values), self::FORM_NAME, $this->session);
		};
		if ($isNice) {
			Assert::noError($check);
			Assert::null($this->nullLogger->getLogged());
		} else {
			Assert::throws($check, SpammyApplicationException::class);
			Assert::same($logged, $this->nullLogger->getLogged());
		}
	}

}

$runner->run(FormSpamTest::class);
