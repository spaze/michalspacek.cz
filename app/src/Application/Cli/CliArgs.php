<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Cli;

readonly class CliArgs
{

	/**
	 * @param array<string, mixed> $args
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


	public function getError(): ?string
	{
		return $this->error;
	}

}