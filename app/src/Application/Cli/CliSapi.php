<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Cli;

use LogicException;

final class CliSapi
{

	/**
	 * Stops what must not run anywhere else than from the command line, which also tells Psalm that what follows the call does:
	 * what it prints goes to a terminal and not to a browser as HTML.
	 *
	 * @psalm-assert 'cli' PHP_SAPI
	 */
	public static function enforce(): void
	{
		if (PHP_SAPI !== 'cli') {
			throw new LogicException('This runs only from the command line, the SAPI is ' . PHP_SAPI);
		}
	}

}
