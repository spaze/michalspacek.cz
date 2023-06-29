<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Utils;

use MichalSpacekCz\Utils\Exceptions\JsonItemNotStringException;
use MichalSpacekCz\Utils\Exceptions\JsonItemsNotArrayException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

class JsonUtils
{

	/**
	 * @return list<string>
	 * @throws JsonItemsNotArrayException
	 * @throws JsonItemNotStringException
	 * @throws JsonException
	 */
	public function decodeListOfStrings(string $json): array
	{
		$result = [];
		$decoded = Json::decode($json);
		if (!is_array($decoded)) {
			throw new JsonItemsNotArrayException($decoded, $json);
		}
		foreach ($decoded as $item) {
			if (!is_string($item)) {
				throw new JsonItemNotStringException($item, $json);
			}
			$result[] = $item;
		}
		return $result;
	}

}
