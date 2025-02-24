<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords\Storage;

final readonly class StorageSharedWith
{

	public function __construct(
		private string $url,
		private string $alias,
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
