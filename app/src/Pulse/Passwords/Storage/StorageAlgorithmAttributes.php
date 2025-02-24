<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords\Storage;

final readonly class StorageAlgorithmAttributes
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
