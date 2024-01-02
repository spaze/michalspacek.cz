<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Dates;

use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Forms\Controls\TextInput;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class TrainingDatesFormValidatorTest extends TestCase
{

	public function __construct(
		private readonly TrainingDatesFormValidator $validator,
	) {
	}


	/**
	 * @return array<string, array{0:string|int, 1:string|int, 2:list<string>}>
	 */
	public function getStartEnd(): array
	{
		return [
			'date ok, time ok' => [
				'2022-06-07 13:00',
				'2022-06-08 18:00',
				[],
			],
			'date ok, time bad' => [
				'2022-06-07 13:00',
				'2022-06-08 13:00',
				[
					'Školení nemůže začínat a končit ve stejný čas',
				],
			],
			'date bad, time ok' => [
				'2022-03-07 13:00',
				'2022-06-08 18:00',
				[
					'Začátek a konec jsou od sebe 93 dní, mohou být maximálně 14',
				],
			],
			'date bad, time bad' => [
				'2022-03-07 13:00',
				'2022-06-08 13:00',
				[
					'Školení nemůže začínat a končit ve stejný čas',
					'Začátek a konec jsou od sebe 93 dní, mohou být maximálně 14',
				],
			],
			'start int, end string' => [
				2022,
				'2022-06-08 18:00',
				[
					'Začátek i školení musí být řetězec',
				],
			],
			'start string, end int' => [
				'2022-03-07 13:00',
				2022,
				[
					'Začátek i školení musí být řetězec',
				],
			],
			'start int, end int' => [
				2022,
				2022,
				[
					'Začátek i školení musí být řetězec',
				],
			],
		];
	}


	/**
	 * @param list<string> $expectedErrors
	 * @dataProvider getStartEnd
	 */
	public function testValidateFormStartEnd(string|int $inputStart, string|int $inputEnd, array $expectedErrors): void
	{
		$start = (new TextInput())->setDefaultValue($inputStart);
		$end = (new TextInput())->setDefaultValue($inputEnd);
		$this->validator->validateFormStartEnd($start, $end);
		Assert::same([], $start->getErrors());
		Assert::same($expectedErrors, $end->getErrors());
	}

}

TestCaseRunner::run(TrainingDatesFormValidatorTest::class);
