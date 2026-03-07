<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Cli;

use Nette\CommandLine\Parser;

interface CliArgsProvider
{

	/**
	 * @return list<string>
	 */
	public static function getArgs(): array;


	/**
	 * @return array<string, array<Parser::Argument|Parser::Optional|Parser::Repeatable|Parser::Enum|Parser::RealPath|Parser::Normalizer|Parser::Default, int|string|bool>>
	 */
	public static function getPositionalArgs(): array;

}
