<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\ApplicationForm;

use Nette\Http\SessionSection;
use stdClass;
use Tracy\Debugger;

class TrainingApplicationFormDataLogger
{

	public function log(stdClass $values, string $name, ?SessionSection $sessionSection): void
	{
		$logValues = $logSession = [];
		foreach ($sessionSection?->get('application')[$name] ?? [] as $key => $value) {
			$logSession[] = sprintf('%s => "%s"', $key, $value);
		}
		foreach ((array)$values as $key => $value) {
			$logValues[] = sprintf('%s => "%s"', $key, $value);
		}
		$message = sprintf(
			'Application session data for %s: %s, form values: %s',
			$name,
			($sessionSection === null ? 'undefined' : ($logSession === [] ? 'empty' : implode(', ', $logSession))),
			($logValues === [] ? 'empty' : implode(', ', $logValues)),
		);
		Debugger::log($message);
	}

}
