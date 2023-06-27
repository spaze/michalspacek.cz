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
		$valueStart = $inputStart->getValue();
		$valueEnd = $inputEnd->getValue();
		if (!is_string($valueStart) || !is_string($valueEnd)) {
			$inputEnd->addError('Začátek i školení musí být řetězec');
			return;
		}
		$start = new DateTimeImmutable($valueStart);
		$end = new DateTimeImmutable($valueEnd);

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
