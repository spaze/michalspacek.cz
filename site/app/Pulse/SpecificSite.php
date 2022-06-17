<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse;

use MichalSpacekCz\Pulse\Passwords\StorageSharedWith;

class SpecificSite extends WildcardSite
{

	/** @var array<int, StorageSharedWith> */
	private array $sharedWith = [];


	/**
	 * @param array<int, array{url:string, alias:string}> $sharedWith
	 */
	public function __construct(
		string $id,
		private readonly string $url,
		private readonly string $alias,
		array $sharedWith,
		Company $company,
		string $storageId,
	) {
		parent::__construct($id, $company, $storageId);
		foreach ($sharedWith as $sharedSite) {
			$this->sharedWith[] = new StorageSharedWith($sharedSite['url'], $sharedSite['alias']);
		}
	}


	public function getUrl(): string
	{
		return $this->url;
	}


	public function getAlias(): string
	{
		return $this->alias;
	}


	/**
	 * @return array<int, StorageSharedWith>
	 */
	public function getSharedWith(): array
	{
		return $this->sharedWith;
	}

}
