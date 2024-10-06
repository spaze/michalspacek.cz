<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse;

readonly class Site
{

	public function __construct(
		private int $id,
		private string $url,
		private string $alias,
	) {
	}


	public function getId(): int
	{
		return $this->id;
	}


	public function getUrl(): string
	{
		return $this->url;
	}


	public function getAlias(): string
	{
		return $this->alias;
	}

}
