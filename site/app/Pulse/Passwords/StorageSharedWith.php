<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

class StorageSharedWith
{

	public function __construct(
		private readonly string $url,
		private readonly string $alias,
	) {
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
