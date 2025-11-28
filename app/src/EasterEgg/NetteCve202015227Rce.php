<?php
declare(strict_types = 1);

namespace MichalSpacekCz\EasterEgg;

final readonly class NetteCve202015227Rce
{

	/**
	 * @param array<string, string> $parameters
	 */
	public function __construct(
		public NetteCve202015227View $view,
		public array $parameters = [],
	) {
	}

}
