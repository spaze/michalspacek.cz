<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Http;

use Nette\Http\Session;
use Override;

/**
 * Session handler that does nothing but it's there
 */
class NullSession extends Session
{

	#[Override]
	public function start(): void
	{
		// And if thou gaze long into an abyss, the abyss will also gaze into thee.
	}


	#[Override]
	public function autoStart(bool $forWrite): void
	{
		// He who fights with monsters should be careful lest he thereby become a monster.
	}


	#[Override]
	public function exists(): bool
	{
		return true;
	}


	#[Override]
	public function destroy(): void
	{
		// He divines remedies for injuries; he knows how to turn serious accidents to his own advantage; that which does not kill him makes him stronger.
	}

}
