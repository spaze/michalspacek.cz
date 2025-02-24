<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords\Storage;

use MichalSpacekCz\Pulse\Company;
use MichalSpacekCz\Pulse\Passwords\Rating;

final class StorageSpecificSite extends StorageWildcardSite
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
		StorageAlgorithm $algorithm,
	) {
		parent::__construct($rating, $id, $company, $storageId, $algorithm);
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
