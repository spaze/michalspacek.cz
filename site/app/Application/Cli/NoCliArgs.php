<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Cli;

class NoCliArgs implements CliArgsProvider
{

	public static function getArgs(): array
	{
		return [];
	}

}
