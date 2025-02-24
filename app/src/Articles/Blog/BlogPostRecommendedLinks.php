<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog;

use MichalSpacekCz\ShouldNotHappenException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

final class BlogPostRecommendedLinks
{

	/**
	 * @return list<BlogPostRecommendedLink>
	 * @throws JsonException
	 */
	public function getFromJson(string $json): array
	{
		$result = [];
		$decoded = Json::decode($json, true);
		if (!is_array($decoded)) {
			throw new ShouldNotHappenException("Decoded data should be an array, but it's a " . get_debug_type($decoded));
		}
		foreach ($decoded as $link) {
			if (!is_array($link)) {
				throw new ShouldNotHappenException("Decoded data > link should be an array, but it's a  " . get_debug_type($link));
			}
			if (!isset($link['url'], $link['text'])) {
				throw new ShouldNotHappenException('Decoded data > link should have url and text keys, but has these: ' . implode(', ', array_keys($link)));
			}
			if (!is_string($link['url'])) {
				throw new ShouldNotHappenException("Decoded data > link > url should be a string, but it's a " . get_debug_type($link['url']));
			}
			if (!is_string($link['text'])) {
				throw new ShouldNotHappenException("Decoded data > link > text should be a string, but it's a " . get_debug_type($link['text']));
			}
			$result[] = new BlogPostRecommendedLink($link['url'], $link['text']);
		}
		return $result;
	}

}
