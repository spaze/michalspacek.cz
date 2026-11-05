<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Cli;

use Nette\CommandLine\Command;

interface CliArgsProvider
{

	/**
	 * @return string|null What the script does, in a sentence the help prints under the usage line; null when there is nothing to say
	 */
	public static function getDescription(): ?string;


	/**
	 * Declare the script's command-line arguments on the command, using its `addFlag()`/`addArgument()`/`addOption()`
	 * methods. `CliArgs::HELP`, `CliArgs::DEBUG` and `CliArgs::COLORS` are already defined, `CliArgs::parse()` gives them
	 * to every script before calling this, so don't define them again: the command refuses a name it already has and the boot dies.
	 */
	public static function defineArgs(Command $command): void;

}
