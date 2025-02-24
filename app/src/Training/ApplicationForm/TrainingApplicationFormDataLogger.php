<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\ApplicationForm;

use MichalSpacekCz\Training\Applications\TrainingApplicationSessionSection;
use Tracy\Debugger;

final class TrainingApplicationFormDataLogger
{

	/**
	 * @param array<array-key, mixed> $values
	 */
	public function log(array $values, string $name, int $dateId, ?TrainingApplicationSessionSection $sessionSection): void
	{
		$applicationId = $sessionSection?->getApplicationIdByDateId($name, $dateId);
		$logSession = $applicationId !== null ? "id => '{$applicationId}', dateId => '{$dateId}'" : null;
		$logValues = [];
		foreach ($values as $key => $value) {
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
