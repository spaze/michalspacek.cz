<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tags;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Nette\Utils\Strings;

class Tags
{

	/**
	 * Convert tags string to array.
	 *
	 * @param string $tags
	 * @return string[]
	 */
	public function toArray(string $tags): array
	{
		$values = Strings::split($tags, '/\s*,\s*/');
		return array_values(array_filter($values));
	}


	/**
	 * @param string[] $tags
	 * @return string
	 */
	public function toString(array $tags): string
	{
		return implode(', ', $tags);
	}


	/**
	 * @param string $tags
	 * @return string[]
	 */
	public function toSlugArray(string $tags): array
	{
		return ($tags ? array_map([Strings::class, 'webalize'], $this->toArray($tags)) : []);
	}


	/**
	 * @param string[] $tags
	 * @return string
	 * @throws JsonException
	 */
	public function serialize(array $tags): string
	{
		return Json::encode($tags);
	}


	/**
	 * @param string $tags
	 * @return string[]
	 * @throws JsonException
	 */
	public function unserialize(string $tags): array
	{
		return Json::decode($tags);
	}

}
