<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tags;

use Contributte\Translation\Translator;
use DateTime;
use MichalSpacekCz\DateTime\DateTimeFactory;
use MichalSpacekCz\Utils\Exceptions\JsonItemNotStringException;
use MichalSpacekCz\Utils\Exceptions\JsonItemsNotArrayException;
use Nette\Database\Explorer;
use Nette\Utils\JsonException;

/**
 * Which tag translates which. A post and its translations have their tags in the same order, like `passwords, machine`
 * in English and `hesla, stroj` in Czech, so a tag translates the tag at the same position in a translation of a post
 * that has it, rather than in a translation table of tags that would have to be kept up. Both posts have to be published,
 * a tag page lists published posts only, so the tag of a post that isn't out may have no page yet.
 *
 * Different posts could pair a tag with different tags, and if each tag page took its translation from whichever post
 * it found first, a page could name a translation that names another page back. So each tag gets at most one translation
 * in each other locale, the one the most posts have, and the translation names it back, so the two pages always point
 * at each other.
 */
final readonly class TagTranslations
{

	public function __construct(
		private Explorer $database,
		private Tags $tags,
		private DateTimeFactory $dateTimeFactory,
		private Translator $translator,
	) {
	}


	/**
	 * The parameters of the tag's page in every locale it has a translation in, itself included, none when it has no translation.
	 *
	 * @return array<string, array{tag: string}> locale => params
	 * @throws JsonException
	 * @throws JsonItemNotStringException
	 * @throws JsonItemsNotArrayException
	 */
	public function getLocaleLinkParams(string $tag): array
	{
		return $this->getAllLocaleLinkParams()[$this->translator->getDefaultLocale()][$tag] ?? [];
	}


	/**
	 * The parameters of the pages of every tag with a translation, in every locale it has one in, itself included.
	 *
	 * @return array<string, array<string, array<string, array{tag: string}>>> locale => tag => locale => params
	 * @throws JsonException
	 * @throws JsonItemNotStringException
	 * @throws JsonItemsNotArrayException
	 */
	public function getAllLocaleLinkParams(): array
	{
		$params = [];
		foreach ($this->getTranslations() as $locale => $tags) {
			foreach ($tags as $tag => $translations) {
				$tag = (string)$tag;
				$params[$locale][$tag][$locale] = ['tag' => $tag];
				foreach ($translations as $translationLocale => $translation) {
					$params[$locale][$tag][$translationLocale] = ['tag' => $translation];
				}
				ksort($params[$locale][$tag]);
			}
		}
		return $params;
	}


	/**
	 * @return array<string, array<array-key, array<string, string>>> locale => tag => locale => translated tag
	 * @throws JsonException
	 * @throws JsonItemNotStringException
	 * @throws JsonItemsNotArrayException
	 */
	private function getTranslations(): array
	{
		$translations = [];
		foreach ($this->getCandidates() as $localeA => $byLocaleB) {
			foreach ($byLocaleB as $localeB => $candidates) {
				usort($candidates, fn(array $x, array $y): int => [$y[2], $x[0], $x[1]] <=> [$x[2], $y[0], $y[1]]);
				foreach ($candidates as [$tagA, $tagB]) {
					if (!isset($translations[$localeA][$tagA][$localeB]) && !isset($translations[$localeB][$tagB][$localeA])) {
						$translations[$localeA][$tagA][$localeB] = $tagB;
						$translations[$localeB][$tagB][$localeA] = $tagA;
					}
				}
			}
		}
		return $translations;
	}


	/**
	 * The pairs of tags at the same position in published translations, with how many posts have the pair.
	 *
	 * @return array<string, array<string, list<array{0: string, 1: string, 2: int}>>> locale => other locale => list of [tag, other locale's tag, posts]
	 * @throws JsonException
	 * @throws JsonItemNotStringException
	 * @throws JsonItemsNotArrayException
	 */
	private function getCandidates(): array
	{
		$now = $this->dateTimeFactory->getNow();
		$groups = [];
		$rows = $this->database->fetchAll(
			'SELECT
				bp.key_translation_group AS translationGroupId,
				l.locale,
				bp.published,
				bp.slug_tags AS slugTags
			FROM blog_posts bp
				JOIN locales l ON l.id_locale = bp.key_locale
			WHERE
				bp.key_translation_group IS NOT NULL
				AND bp.slug_tags IS NOT NULL',
		);
		foreach ($rows as $row) {
			assert(is_int($row->translationGroupId));
			assert(is_string($row->locale));
			assert($row->published instanceof DateTime || $row->published === null);
			assert(is_string($row->slugTags));
			if ($row->published !== null && $row->published <= $now) {
				$groups[$row->translationGroupId][$row->locale] = $this->tags->unserialize($row->slugTags);
			}
		}

		$candidates = [];
		foreach ($groups as $posts) {
			foreach ($posts as $localeA => $tagsA) {
				foreach ($posts as $localeB => $tagsB) {
					if ($localeA >= $localeB) {
						continue;
					}
					foreach ($tagsA as $position => $tagA) {
						if (!isset($tagsB[$position])) {
							continue;
						}
						$key = "{$tagA}\n{$tagsB[$position]}";
						$candidates[$localeA][$localeB][$key] ??= [$tagA, $tagsB[$position], 0];
						$candidates[$localeA][$localeB][$key][2]++;
					}
				}
			}
		}
		return array_map(fn(array $byLocaleB): array => array_map(array_values(...), $byLocaleB), $candidates);
	}

}
