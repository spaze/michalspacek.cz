<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\ApplicationForm;

use MichalSpacekCz\Training\Applications\TrainingApplicationSessionSection;
use stdClass;
use Tracy\Debugger;

class TrainingApplicationFormDataLogger
{

	public function log(stdClass $values, string $name, int $dateId, ?TrainingApplicationSessionSection $sessionSection): void
	{
		$applicationId = $sessionSection?->getApplicationIdByDateId($name, $dateId);
		$logSession = $applicationId !== null ? "id => '{$applicationId}', dateId => '{$dateId}'" : null;
		$logValues = [];
		foreach ((array)$values as $key => $value) {
			$logValues[] = sprintf('%s => %s', $key, is_string($value) ? "'{$value}'" : get_debug_type($value));
		}
		$message = sprintf(
			'Application session data for %s: %s, form values: %s',
			$name,
			($sessionSection === null ? 'undefined' : ($logSession === null ? 'empty' : $logSession)),
			($logValues === [] ? 'empty' : implode(', ', $logValues)),
		);
		Debugger::log($message);
	}

}
