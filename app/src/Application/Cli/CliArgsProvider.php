<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Cli;

interface CliArgsProvider
{

	/**
	 * @return list<string>
	 */
	public static function getArgs(): array;

}
