<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Cli;

class CliArgs
{

	/**
	 * @param array<string, mixed> $args
	 */
	public function __construct(
		private readonly array $args,
		private readonly ?string $error,
	) {
	}


	public function getFlag(string $arg): bool
	{
		return ($this->args[$arg] ?? null) === true;
	}


	public function getError(): ?string
	{
		return $this->error;
	}

}
