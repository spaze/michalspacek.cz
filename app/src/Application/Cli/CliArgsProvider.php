<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Cli;

use Nette\CommandLine\Parser;

interface CliArgsProvider
{

	/**
	 * Declare the script's command-line arguments on the parser, using its
	 * `addSwitch()`/`addArgument()`/`addOption()` builders.
	 */
	public static function defineArgs(Parser $parser): void;

}
