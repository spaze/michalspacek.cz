<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Cli;

use MichalSpacekCz\Application\Cli\CliArgsProvider;

class NoCliArgs implements CliArgsProvider
{

	public static function getArgs(): array
	{
		return [];
	}

}
