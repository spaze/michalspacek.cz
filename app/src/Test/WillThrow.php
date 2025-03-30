<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test;

use Exception;

trait WillThrow
{

	private ?Exception $willThrow = null;


	public function willThrow(Exception $e): void
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
			throw $this->willThrow;
		}
	}

}
