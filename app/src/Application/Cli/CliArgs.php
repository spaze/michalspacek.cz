<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Cli;

final readonly class CliArgs
{

	/**
	 * @param array<array-key, mixed> $args
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
