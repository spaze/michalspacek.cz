<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tags;

use MichalSpacekCz\Utils\Exceptions\JsonItemNotStringException;
use MichalSpacekCz\Utils\Exceptions\JsonItemsNotArrayException;
use MichalSpacekCz\Utils\JsonUtils;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Nette\Utils\Strings;

class Tags
{

	public function __construct(
		private readonly JsonUtils $jsonUtils,
	) {
	}


	/**
	 * Convert tags string to array.
	 *
	 * @param string $tags
	 * @return list<string>
	 */
	public function toArray(string $tags): array
	{
		$values = Strings::split($tags, '/\s*,\s*/');
		return array_values(array_filter($values));
	}


	/**
	 * @param list<string> $tags
	 */
	public function toString(array $tags): string
	{
		return implode(', ', $tags);
	}


	/**
	 * @param string $tags
	 * @return list<string>
	 */
	public function toSlugArray(string $tags): array
	{
		return ($tags ? array_map([Strings::class, 'webalize'], $this->toArray($tags)) : []);
	}


	/**
	 * @param list<string> $tags
	 * @throws JsonException
	 */
	public function serialize(array $tags): string
	{
		return Json::encode($tags);
	}


	/**
	 * @param string $tags
	 * @return list<string>
	 * @throws JsonException
	 * @throws JsonItemNotStringException
	 * @throws JsonItemsNotArrayException
	 */
	public function unserialize(string $tags): array
	{
		return $this->jsonUtils->decodeListOfStrings($tags);
	}

}
