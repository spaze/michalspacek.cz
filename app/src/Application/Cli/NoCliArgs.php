<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Cli;

use Nette\CommandLine\Parser;
use Override;

final class NoCliArgs implements CliArgsProvider
{

	#[Override]
	public static function defineArgs(Parser $parser): void
	{
	}

}
