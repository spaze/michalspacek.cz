<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Http;

use Nette\Http\Session;

/**
 * Session handler that does nothing but it's there
 */
class NullSession extends Session
{

	private bool $destroyCalled = false;


	public function start(): void
	{
		// And if thou gaze long into an abyss, the abyss will also gaze into thee.
	}


	public function autoStart(bool $forWrite): void
	{
		// He who fights with monsters should be careful lest he thereby become a monster.
	}


	public function exists(): bool
	{
		return true;
	}


	public function destroy(): void
	{
		$this->destroyCalled = true;
	}


	public function destroyCalled(): bool
	{
		return $this->destroyCalled;
	}

}
