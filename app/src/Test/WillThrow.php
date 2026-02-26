<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test;

use Closure;
use Exception;

trait WillThrow
{

	/** @var Exception|(Closure(): Exception)|null */
	private Exception|Closure|null $willThrow = null;


	/**
	 * @param Exception|Closure(): Exception $e
	 */
	public function willThrow(Exception|Closure $e): void
	{
		$this->willThrow = $e;
	}


	public function wontThrow(): void
	{
		$this->willThrow = null;
	}


	private function maybeThrow(): void
	{
		if ($this->willThrow !== null) {
			$e = $this->willThrow instanceof Closure ? ($this->willThrow)() : $this->willThrow;
			throw $e;
		}
	}

}
