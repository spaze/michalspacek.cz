<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Cli;

use Nette\CommandLine\Command;
use Override;

final class NoCliArgs implements CliArgsProvider
{

	#[Override]
	public static function getDescription(): ?string
	{
		return null;
	}


	#[Override]
	public static function defineArgs(Command $command): void
	{
	}

}
