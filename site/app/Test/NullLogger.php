<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test;

use Tracy\ILogger;

class NullLogger implements ILogger
{

	/** @var array<int, mixed> */
	private array $logged = [];


	/**
	 * @param mixed $value
	 * @param string $level
	 * @return string|null
	 */
	public function log($value, $level = self::INFO): ?string
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
