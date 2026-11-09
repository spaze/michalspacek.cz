<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords\Storage;

use MichalSpacekCz\Utils\Exceptions\JsonItemNotStructureException;
use MichalSpacekCz\Utils\Exceptions\JsonItemsNotArrayException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

final readonly class StorageSharedWithFactory
{

	/**
	 * @return list<StorageSharedWith>
	 * @throws JsonException
	 * @throws JsonItemsNotArrayException
	 * @throws JsonItemNotStructureException
	 */
	public function get(string $json): array
	{
		$sharedWith = [];
		$items = Json::decode($json, forceArrays: true);
		if (!is_array($items)) {
			throw new JsonItemsNotArrayException($items, $json);
		}
		foreach ($items as $item) {
			if (!is_array($item) || !isset($item['url']) || !isset($item['alias']) || !is_string($item['url']) || !is_string($item['alias'])) {
				throw new JsonItemNotStructureException($item, ['url', 'alias'], $json);
			}
			$sharedWith[] = new StorageSharedWith($item['url'], $item['alias']);
		}
		return $sharedWith;
	}

}
