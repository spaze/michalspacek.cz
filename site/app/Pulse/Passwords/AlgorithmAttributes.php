<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

class AlgorithmAttributes
{

	/**
	 * @param list<string>|null $inner
	 * @param list<string>|null $outer
	 * @param array<string, string|int>|null $params
	 */
	public function __construct(
		private readonly ?array $inner,
		private readonly ?array $outer,
		private readonly ?array $params,
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
	 * @return array<string, string>|null
	 */
	public function getParams(): ?array
	{
		return $this->params;
	}

}
