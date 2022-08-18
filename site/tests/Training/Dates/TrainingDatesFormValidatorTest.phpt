<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use MichalSpacekCz\Training\Dates\TrainingDatesFormValidator;
use Nette\Forms\Controls\TextInput;
use Tester\Assert;
use Tester\TestCase;

$container = require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class TrainingDatesFormValidatorTest extends TestCase
{

	public function __construct(
		private readonly TrainingDatesFormValidator $validator,
	) {
	}


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
		];
	}


	/** @dataProvider getStartEnd */
	public function testValidateFormStartEnd(string $inputStart, string $inputEnd, array $expectedErrors): void
	{
		$start = (new TextInput())->setDefaultValue($inputStart);
		$end = (new TextInput())->setDefaultValue($inputEnd);
		$this->validator->validateFormStartEnd($start, $end);
		Assert::same([], $start->getErrors());
		Assert::same($expectedErrors, $end->getErrors());
	}

}

(new TrainingDatesFormValidatorTest(
	$container->getByType(TrainingDatesFormValidator::class),
))->run();
