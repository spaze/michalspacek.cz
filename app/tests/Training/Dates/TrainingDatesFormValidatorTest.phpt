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
final class TrainingDatesFormValidatorTest extends TestCase
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


	/**
	 * An empty start or end would be now, starting and ending at the same time, and one not a date would throw.
	 */
	public function testValidateFormStartEndFailedRules(): void
	{
		$values = [
			'start empty' => ['', '2022-06-08 18:00'],
			'end empty' => ['2022-06-07 13:00', ''],
			'start not a date' => ['foo', '2022-06-08 18:00'],
			'end not a date' => ['2022-06-07 13:00', 'foo'],
		];
		$results = [];
		foreach ($values as $name => [$valueStart, $valueEnd]) {
			$start = (new TextInput())->setDefaultValue($valueStart);
			$end = (new TextInput())->setDefaultValue($valueEnd);
			($valueStart === '' || $valueStart === 'foo' ? $start : $end)->addError('rule failed');
			$this->validator->validateFormStartEnd($start, $end);
			$results[$name] = [...$start->getErrors(), ...$end->getErrors()];
		}
		Assert::same(array_fill_keys(array_keys($values), ['rule failed']), $results);
	}

}

TestCaseRunner::run(TrainingDatesFormValidatorTest::class);
