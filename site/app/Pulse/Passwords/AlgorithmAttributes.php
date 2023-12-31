<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

readonly class AlgorithmAttributes
{

	/**
	 * @param list<string>|null $inner
	 * @param list<string>|null $outer
	 * @param array<string, string|int>|null $params
	 */
	public function __construct(
		private ?array $inner,
		private ?array $outer,
		private ?array $params,
	) {
	}


	/**
	 * @return list<string>|null
	 */
	public function getInner(): ?array
	{
		return $this->inner;
	}


	/**
	 * @return list<string>|null
	 */
	public function getOuter(): ?array
	{
		return $this->outer;
	}


	/**
	 * @return array<string, string|int>|null
	 */
	public function getParams(): ?array
	{
		return $this->params;
	}

}
