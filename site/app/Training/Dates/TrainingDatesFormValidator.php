<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Dates;

use DateTimeImmutable;
use Exception;
use Nette\Forms\Controls\TextInput;

class TrainingDatesFormValidator
{

	/**
	 * @throws Exception
	 */
	public function validateFormStartEnd(TextInput $inputStart, TextInput $inputEnd): void
	{
		$start = new DateTimeImmutable($inputStart->getValue());
		$end = new DateTimeImmutable($inputEnd->getValue());

		if ($end->format('H:i') === $start->format('H:i')) {
			$inputEnd->addError('Školení nemůže začínat a končit ve stejný čas');
		}
		$days = $end->diff($start)->days;
		$maxDays = 14;
		if ($days > $maxDays) {
			$inputEnd->addError("Začátek a konec jsou od sebe {$days} dní, mohou být maximálně {$maxDays}");
		}
	}

}
