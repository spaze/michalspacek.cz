<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test;

use Override;
use Tracy\ILogger;

final class NullLogger implements ILogger
{

	/** @var array<int, mixed> */
	private array $logged = [];


	#[Override]
	public function log(mixed $value, string $level = self::INFO): ?string
	{
		$this->logged[] = $value;
		return null;
	}


	/**
	 * @return array<int, mixed>
	 */
	public function getLogged(): array
	{
		return $this->logged;
	}


	public function reset(): void
	{
		$this->logged = [];
	}

}
