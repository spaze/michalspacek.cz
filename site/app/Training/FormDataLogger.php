<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use Nette\Http\SessionSection;
use stdClass;
use Tracy\Debugger;

class FormDataLogger
{

	public function log(stdClass $values, string $name, SessionSection $sessionSection): void
	{
		$logValues = $logSession = [];
		if (isset($sessionSection->application[$name])) {
			foreach ($sessionSection->application[$name] as $key => $value) {
				$logSession[] = "{$key} => \"{$value}\"";
			}
		}
		foreach ((array)$values as $key => $value) {
			$logValues[] = "{$key} => \"{$value}\"";
		}
		$message = sprintf(
			'Application session data for %s: %s, form values: %s',
			$name,
			(empty($logSession) ? 'empty' : implode(', ', $logSession)),
			implode(', ', $logValues)
		);
		Debugger::log($message);
	}

}
