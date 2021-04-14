<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use Nette\Utils\ArrayHash;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class FormSpamTest extends TestCase
{

	private FormSpam $formSpam;


	protected function setUp(): void
	{
		$this->formSpam = new FormSpam();
	}


	public function getValues(): array
	{
		return [
			[
				[
					'note' => 'foo href="https:// example" bar baz',
				],
			],
			[
				[
					'name' => 'zggnbijhah',
					'companyId' => 'vwetyeofcx',
					'companyTaxId' => 'tyqvukaims',
					'company' => 'qzpormrfcq',
				],
			],
		];
	}


	/**
	 * @dataProvider getValues
	 */
	public function testIsSpam(array $values): void
	{
		Assert::true($this->formSpam->isSpam(ArrayHash::from($values)));
	}

}

(new FormSpamTest())->run();
