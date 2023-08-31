<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse;

use MichalSpacekCz\Pulse\Passwords\Rating;
use MichalSpacekCz\Pulse\Passwords\StorageSharedWith;

class SpecificSite extends WildcardSite
{

	/**
	 * @param list<StorageSharedWith> $sharedWith
	 */
	public function __construct(
		Rating $rating,
		string $id,
		private readonly string $url,
		private readonly string $alias,
		private readonly array $sharedWith,
		Company $company,
		string $storageId,
	) {
		parent::__construct($rating, $id, $company, $storageId);
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
	 * @return list<StorageSharedWith>
	 */
	public function getSharedWith(): array
	{
		return $this->sharedWith;
	}

}
