<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Utils;

use MichalSpacekCz\Test\NoOpTranslator;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Utils\Strings;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class StringsTest extends TestCase
{

	public function __construct(
		private readonly Strings $strings,
		private readonly NoOpTranslator $translator,
	) {
	}


	/**
	 * @return list<array{0:string, 1:string, 2:string}>
	 */
	public function getInitials(): array
	{
		return [
			['Chrome', 'C', 'Ch'],
			['chrome', 'C', 'Ch'],
			['Firefox', 'F', 'F'],
			['firefox', 'F', 'F'],
			['Ch', 'C', 'Ch'],
			['CH', 'C', 'Ch'],
			['cH', 'C', 'Ch'],
			['C', 'C', 'C'],
			['c', 'C', 'C'],
			['', '', ''],
			[' ', ' ', ' '],
			['-', '-', '-'],
			['-Foo', '-', '-'],
			[' Chrome', ' ', ' '],
			['🍦', '🍦', '🍦'],
			['🧊 cream', '🧊', '🧊'],
		];
	}


	/**
	 * @dataProvider getInitials
	 */
	public function testGetInitialLetterUppercase(string $string, string $enInitial, string $csInitial): void
	{
		$this->translator->setDefaultLocale('en_US');
		Assert::same($enInitial, $this->strings->getInitialLetterUppercase($string));

		$this->translator->setDefaultLocale('cs_CZ');
		Assert::same($csInitial, $this->strings->getInitialLetterUppercase($string));
	}


	public function testLength(): void
	{
		Assert::same(9, $this->strings->length('ěščřžýáíé'));
	}

}

TestCaseRunner::run(StringsTest::class);
