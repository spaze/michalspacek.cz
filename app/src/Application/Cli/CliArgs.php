<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Cli;

use LogicException;

final readonly class CliArgs
{

	/**
	 * @param array<array-key, string|true|null> $args
	 */
	public function __construct(
		private array $args,
		private ?string $error,
	) {
	}


	public function getFlag(string $arg): bool
	{
		return ($this->args[$arg] ?? null) === true;
	}


	public function getArg(string $arg): string
	{
		if (!array_key_exists($arg, $this->args)) {
			throw new LogicException("Argument {$arg} is not defined by the args provider");
		}
		if (!is_string($this->args[$arg])) {
			throw new LogicException("Argument {$arg} is not a string");
		}
		return $this->args[$arg];
	}


	public function getError(): ?string
	{
		return $this->error;
	}

}
