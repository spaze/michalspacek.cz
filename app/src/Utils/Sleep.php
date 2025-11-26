<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Utils;

class Sleep
{

	public function randomSleep(int $min, int $max): void
	{
		sleep(random_int($min, $max));
	}

}
