<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Makefile\Exceptions;

use Throwable;

final class MakefileContainsRealTargetsException extends MakefileException
{

	/**
	 * @param array<string, list<int>> $notPhonyTargets
	 */
	public function __construct(array $notPhonyTargets, ?Throwable $previous = null)
	{
		$multipleTargets = count($notPhonyTargets) > 1;
		$message = 'Makefile contains ' . ($multipleTargets ? 'real targets' : 'a real target') . ":\n";
		foreach ($notPhonyTargets as $target => $lines) {
			$message .= sprintf("- `%s` defined on %s %s\n", $target, count($lines) > 1 ? 'lines' : 'line', implode(', ', $lines));
		}
		$message .= "Add " . ($multipleTargets ? 'them' : 'it') . " to a .PHONY target!";
		parent::__construct($message, previous: $previous);
	}

}
