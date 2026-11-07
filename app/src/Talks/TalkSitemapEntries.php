<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks;

use MichalSpacekCz\Sitemap\SitemapEntries;
use MichalSpacekCz\Sitemap\SitemapEntry;
use Nette\Database\Explorer;
use Override;

/**
 * Every talk with a page, upcoming ones too, a talk and its translations as one entry. A talk showing the slides of
 * another talk is left out: its page names that talk as the canonical one, and a sitemap lists canonical pages only.
 */
final readonly class TalkSitemapEntries implements SitemapEntries
{

	public function __construct(
		private Explorer $database,
	) {
	}


	#[Override]
	public function getEntries(): array
	{
		$rows = $this->database->fetchAll(
			'SELECT
				t.action,
				l.locale,
				t.key_translation_group AS translationGroupId
			FROM talks t
				JOIN locales l ON l.id_locale = t.key_locale
			WHERE
				t.action IS NOT NULL
				AND t.key_talk_slides IS NULL
			ORDER BY t.date DESC',
		);
		$params = [];
		foreach ($rows as $row) {
			assert(is_string($row->action));
			assert(is_string($row->locale));
			assert($row->translationGroupId === null || is_int($row->translationGroupId));
			$key = $row->translationGroupId !== null ? "group/{$row->translationGroupId}" : "talk/{$row->action}";
			$params[$key][$row->locale] = ['name' => $row->action];
		}
		return array_values(array_map(fn(array $localeParams): SitemapEntry => new SitemapEntry($localeParams), $params));
	}

}
