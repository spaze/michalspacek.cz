<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog;

use Contributte\Translation\Translator;
use DateTime;
use MichalSpacekCz\Application\LinkGenerator;
use MichalSpacekCz\Application\Locale\LocaleLinkGenerator;
use MichalSpacekCz\DateTime\Exceptions\InvalidTimezoneException;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Tags\Tags;
use MichalSpacekCz\Twitter\TwitterCard;
use MichalSpacekCz\Twitter\TwitterCards;
use MichalSpacekCz\Utils\Exceptions\JsonItemNotStringException;
use MichalSpacekCz\Utils\Exceptions\JsonItemsNotArrayException;
use MichalSpacekCz\Utils\JsonUtils;
use Nette\Application\UI\InvalidLinkException;
use Nette\Database\Row;
use Nette\Utils\JsonException;

final readonly class BlogPostFactory
{

	/**
	 * @param array<string, array<string, list<string>>> $allowedTags
	 */
	public function __construct(
		private TexyFormatter $texyFormatter,
		private Tags $tags,
		private TwitterCards $twitterCards,
		private BlogPostRecommendedLinks $recommendedLinks,
		private BlogPostEdits $edits,
		private JsonUtils $jsonUtils,
		private LinkGenerator $linkGenerator,
		private LocaleLinkGenerator $localeLinkGenerator,
		private Translator $translator,
		private array $allowedTags,
	) {
	}


	/**
	 * @param list<string> $tags
	 * @param list<string> $slugTags
	 * @param list<BlogPostRecommendedLink> $recommended
	 * @param list<string> $cspSnippets
	 * @param list<string> $allowedTagsGroups
	 * @throws InvalidLinkException
	 * @throws InvalidTimezoneException
	 */
	public function create(
		?int $id,
		string $slug,
		int $localeId,
		?string $locale,
		?int $translationGroupId,
		string $titleTexy,
		?string $leadTexy,
		string $textTexy,
		?DateTime $published,
		?string $previewKey,
		?string $originallyTexy,
		?string $ogImage,
		array $tags,
		array $slugTags,
		array $recommended,
		?TwitterCard $twitterCard,
		array $cspSnippets,
		array $allowedTagsGroups,
		bool $omitExports,
	): BlogPost {
		$texy = $this->texyFormatter->getTexy();
		$texyFormatter = $this->texyFormatter->withTexy($texy);

		if ($allowedTagsGroups) {
			$allowedTags = [];
			foreach ($allowedTagsGroups as $allowedTagsGroup) {
				$allowedTags = array_merge($allowedTags, $this->allowedTags[$allowedTagsGroup]);
			}
			$texy->allowedTags = $allowedTags;
		}
		$texyFormatter->setTopHeading(2);

		$needsPreviewKey = $published === null || $published > new DateTime();
		return new BlogPost(
			$id,
			$slug,
			$localeId,
			$locale,
			$translationGroupId,
			$texyFormatter->format($titleTexy),
			$titleTexy,
			$leadTexy !== null ? $texyFormatter->formatBlock($leadTexy) : null,
			$leadTexy,
			$texyFormatter->formatBlock($textTexy),
			$textTexy,
			$published,
			$needsPreviewKey,
			$previewKey,
			$originallyTexy !== null ? $texyFormatter->formatBlock($originallyTexy) : null,
			$originallyTexy,
			$ogImage,
			$tags,
			$slugTags,
			$recommended,
			$twitterCard,
			$this->getHref($slug, $needsPreviewKey ? $previewKey : null, $locale),
			$id !== null ? $this->edits->getEdits($id) : [],
			$cspSnippets,
			$allowedTagsGroups,
			$omitExports,
		);
	}


	/**
	 * @throws InvalidLinkException
	 * @throws JsonException
	 * @throws JsonItemNotStringException
	 * @throws JsonItemsNotArrayException
	 * @throws InvalidTimezoneException
	 */
	public function createFromDatabaseRow(Row $row): BlogPost
	{
		assert(is_int($row->id));
		assert(is_string($row->slug));
		assert(is_int($row->localeId));
		assert(is_string($row->locale));
		assert($row->translationGroupId === null || is_int($row->translationGroupId));
		assert(is_string($row->titleTexy));
		assert($row->leadTexy === null || is_string($row->leadTexy));
		assert(is_string($row->textTexy));
		assert($row->published instanceof DateTime);
		assert($row->previewKey === null || is_string($row->previewKey));
		assert($row->originallyTexy === null || is_string($row->originallyTexy));
		assert($row->ogImage === null || is_string($row->ogImage));
		assert($row->tags === null || is_string($row->tags));
		assert($row->slugTags === null || is_string($row->slugTags));
		assert($row->recommended === null || is_string($row->recommended));
		assert($row->twitterCardId === null || is_int($row->twitterCardId));
		assert($row->twitterCard === null || is_string($row->twitterCard));
		assert($row->twitterCardTitle === null || is_string($row->twitterCardTitle));
		assert($row->cspSnippets === null || is_string($row->cspSnippets));
		assert($row->allowedTags === null || is_string($row->allowedTags));
		assert($row->omitExports === null || is_int($row->omitExports));

		return $this->create(
			$row->id,
			$row->slug,
			$row->localeId,
			$row->locale,
			$row->translationGroupId,
			$row->titleTexy,
			$row->leadTexy,
			$row->textTexy,
			$row->published,
			$row->previewKey,
			$row->originallyTexy,
			$row->ogImage,
			$row->tags !== null ? $this->tags->unserialize($row->tags) : [],
			$row->slugTags !== null ? $this->tags->unserialize($row->slugTags) : [],
			$row->recommended === null || $row->recommended === '' ? [] : $this->recommendedLinks->getFromJson($row->recommended),
			isset($row->twitterCardId, $row->twitterCard, $row->twitterCardTitle) ? $this->twitterCards->buildCard($row->twitterCardId, $row->twitterCard, $row->twitterCardTitle) : null,
			$row->cspSnippets !== null ? $this->jsonUtils->decodeListOfStrings($row->cspSnippets) : [],
			$row->allowedTags !== null ? $this->jsonUtils->decodeListOfStrings($row->allowedTags) : [],
			(bool)$row->omitExports,
		);
	}


	/**
	 * @throws InvalidLinkException
	 */
	private function getHref(string $slug, ?string $previewKey, ?string $locale): string
	{
		$params = [
			'slug' => $slug,
			'preview' => $previewKey,
		];
		if ($locale === null || $locale === $this->translator->getDefaultLocale()) {
			return $this->linkGenerator->link('Www:Post:', $params);
		} else {
			$links = $this->localeLinkGenerator->links('Www:Post:', $this->localeLinkGenerator->defaultParams($params));
			return $links[$locale]->getUrl();
		}
	}


	/**
	 * @return array<string, array<string, list<string>>>
	 */
	public function getAllowedTags(): array
	{
		return $this->allowedTags;
	}

}
