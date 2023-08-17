<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use MichalSpacekCz\Test\NullLogger;
use MichalSpacekCz\Training\Exceptions\SpammyApplicationException;
use Nette\Utils\ArrayHash;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class FormSpamTest extends TestCase
{

	public function __construct(
		private readonly NullLogger $nullLogger,
		private readonly FormSpam $formSpam,
	) {
	}


	/**
	 * @return list<array{0:array<string, string>, 1:bool}>
	 */
	public function getValues(): array
	{
		return [
			[
				[
					'note' => 'foo href="https:// example" bar baz',
				],
				false,
			],
			[
				[
					'name' => 'zggnbijhah',
					'companyId' => 'vwetyeofcx',
					'companyTaxId' => 'tyqvukaims',
					'company' => 'qzpormrfcq',
				],
				false,
			],
			[
				[
					'name' => 'zggnbijhah',
				],
				false,
			],
			[
				[],
				false,
			],
			[
				[
					'name' => 'foo bar',
				],
				true,
			],
			[
				[
					'companyId' => 'foobar1',
				],
				true,
			],
			[
				[
					'companyTaxId' => 'foobar1',
				],
				true,
			],
		];
	}


	/**
	 * @param array<string, string> $values
	 * @dataProvider getValues
	 */
	public function testIsSpam(array $values, bool $isNice): void
	{
		$check = function () use ($values): void {
			$this->formSpam->check(ArrayHash::from($values));
		};
		if ($isNice) {
			Assert::noError($check);
		} else {
			Assert::exception($check, SpammyApplicationException::class);
		}
		Assert::same([], $this->nullLogger->getAllLogged());
	}

}

$runner->run(FormSpamTest::class);
