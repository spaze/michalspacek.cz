<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tags;

use Composer\Pcre\Preg;
use MichalSpacekCz\Articles\ArticlePublishedElsewhere;
use MichalSpacekCz\Articles\Blog\BlogPost;
use MichalSpacekCz\Articles\Blog\BlogPostLocaleUrls;
use MichalSpacekCz\Utils\Arrays;
use MichalSpacekCz\Utils\Exceptions\JsonItemNotStringException;
use MichalSpacekCz\Utils\Exceptions\JsonItemsNotArrayException;
use MichalSpacekCz\Utils\JsonUtils;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Nette\Utils\Strings;

final readonly class Tags
{

	public function __construct(
		private JsonUtils $jsonUtils,
		private BlogPostLocaleUrls $blogPostLocaleUrls,
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
		$values = Preg::split('/\s*,\s*/', $tags);
		return array_values(Arrays::filterEmpty($values));
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
		return $tags !== '' ? array_map([Strings::class, 'webalize'], $this->toArray($tags)) : [];
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


	/**
	 * Find translated tags.
	 *
	 * Tags in various locales must have the same order, e.g.:
	 * - tags in English: passwords, machine
	 * - tags in Czech: hesla, stroj
	 * This seems a bit weird but otherwise, we'd have to use and build and maintain a translation table for tags. Thanks, but no, thanks.
	 *
	 * @param list<ArticlePublishedElsewhere|BlogPost> $articles
	 * @return array<string, array<string, string>>
	 * @throws JsonException
	 */
	public function findLocaleLinkParams(array $articles, string $tag): array
	{
		$localeLinkParams = [];
		foreach ($articles as $article) {
			if (!$article instanceof BlogPost) {
				continue;
			}
			$posts = $this->blogPostLocaleUrls->get($article->getSlug());
			if (count($posts) === 1) {
				continue; // post and tags not translated yet
			}
			$tagKey = array_search($tag, $article->getSlugTags(), true);
			foreach ($posts as $post) {
				if (isset($post->getSlugTags()[$tagKey])) {
					$localeLinkParams[$post->getLocale()] = ['tag' => $post->getSlugTags()[$tagKey]];
				}
			}
			if (isset($post) && isset($localeLinkParams[$post->getLocale()])) {
				break;
			}
		}
		return $localeLinkParams;
	}

}
