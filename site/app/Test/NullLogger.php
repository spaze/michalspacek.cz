<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test;

use Tracy\ILogger;

class NullLogger implements ILogger
{

	private mixed $logged;

	/** @var array<int, mixed> */
	private array $allLogged = [];


	/**
	 * @param mixed $value
	 * @param string $level
	 * @return string|null
	 */
	public function log($value, $level = self::INFO): ?string
	{
		$this->logged = $value;
		$this->allLogged[] = $value;
		return null;
	}


	public function getLogged(): mixed
	{
		return $this->logged;
	}


	/**
	 * @return array<int, mixed>
	 */
	public function getAllLogged(): array
	{
		return $this->allLogged;
	}


	public function reset(): void
	{
		$this->logged = null;
		$this->allLogged = [];
	}

}
