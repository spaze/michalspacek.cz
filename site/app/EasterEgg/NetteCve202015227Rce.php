<?php
declare(strict_types = 1);

namespace MichalSpacekCz\EasterEgg;

class NetteCve202015227Rce
{

	/**
	 * @param array<string, string> $parameters
	 */
	public function __construct(
		private readonly NetteCve202015227View $view,
		private readonly array $parameters,
	) {
	}


	public function getView(): NetteCve202015227View
	{
		return $this->view;
	}


	/**
	 * @return array<string, string>
	 */
	public function getParameters(): array
	{
		return $this->parameters;
	}

}
