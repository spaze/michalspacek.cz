<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Utils;

use MichalSpacekCz\Utils\Sleep;
use Override;

final class Insomnia extends Sleep
{

	public int $minRandom = 0;

	public int $maxRandom = 0;


	#[Override]
	public function randomSleep(int $min, int $max): void
	{
		$this->minRandom = $min;
		$this->maxRandom = $max;
	}

}
