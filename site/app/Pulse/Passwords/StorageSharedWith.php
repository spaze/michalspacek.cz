<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

class StorageSharedWith
{

	private string $url;

	private string $alias;


	public function __construct(string $url, string $alias)
	{
		$this->url = $url;
		$this->alias = $alias;
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
