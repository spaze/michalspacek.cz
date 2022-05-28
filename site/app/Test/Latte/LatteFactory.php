<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Latte;

use Latte\Engine;
use Nette\Bridges\ApplicationLatte\LatteFactory as NetteLatteFactory;

class LatteFactory implements NetteLatteFactory
{

	public function create(): Engine
	{
		return new Engine();
	}

}
