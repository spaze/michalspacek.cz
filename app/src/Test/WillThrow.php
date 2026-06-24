<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test;

use Closure;
use Exception;

trait WillThrow
{

	/** @var Exception|(Closure(): Exception)|null */
	private Exception|Closure|null $willThrow = null;

	private ?Exception $willThrowOnce = null;


	/**
	 * @param Exception|Closure(): Exception $e
	 */
	public function willThrow(Exception|Closure $e): void
	{
		$this->willThrow = $e;
	}


	/**
	 * Throws on the next maybeThrow() only, then is consumed. Can be combined with willThrow() to
	 * throw this on the first call and then keep throwing that.
	 */
	public function willThrowOnce(Exception $e): void
	{
		$this->willThrowOnce = $e;
	}


	public function wontThrow(): void
	{
		$this->willThrow = null;
		$this->willThrowOnce = null;
	}


	private function maybeThrow(): void
	{
		if ($this->willThrowOnce !== null) {
			$e = $this->willThrowOnce;
			$this->willThrowOnce = null;
			throw $e;
		}
		if ($this->willThrow !== null) {
			throw $this->willThrow instanceof Closure ? ($this->willThrow)() : $this->willThrow;
		}
	}

}
