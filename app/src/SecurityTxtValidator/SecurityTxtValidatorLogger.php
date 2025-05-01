<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator;

use Throwable;
use Tracy\Debugger;

final readonly class SecurityTxtValidatorLogger
{

	public function log(string $host, string $message): void
	{
		Debugger::log("{$host}: {$message}", 'securitytxtvalidator');
	}


	public function logException(string $host, Throwable $e): void
	{
		$message = $e->getMessage();
		if ($e->getPrevious() !== null) {
			$message .= ' (previous: ' . $e->getPrevious()->getMessage() . ')';
		}
		$this->log($host, $message);
	}

}
