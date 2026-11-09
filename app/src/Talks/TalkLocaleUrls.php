<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks;

use MichalSpacekCz\Database\TypedDatabase;

final readonly class TalkLocaleUrls
{

	public function __construct(
		private TypedDatabase $database,
	) {
	}


	/**
	 * The talk in every language it has a page in.
	 *
	 * @return array<string, array{name: string}> locale => ['name' => action]
	 */
	public function getLinkParams(Talk $talk): array
	{
		if ($talk->getTranslationGroupId() === null) {
			return [];
		}
		$actions = $this->database->fetchPairsStringString(
			'SELECT l.locale, t.action FROM talks t JOIN locales l ON t.key_locale = l.id_locale WHERE t.key_translation_group = ? AND t.action IS NOT NULL',
			$talk->getTranslationGroupId(),
		);
		return array_map(fn($action) => ['name' => $action], $actions);
	}


	/**
	 * The talk in every language its page is the canonical one in, for the alternates in the head. A talk showing the
	 * slides of another talk names that talk as canonical, so it gets no alternates and isn't one, like in the sitemap.
	 *
	 * @return array<string, array{name: string}> locale => ['name' => action]
	 */
	public function getAlternateLinkParams(Talk $talk): array
	{
		if ($talk->getTranslationGroupId() === null || $talk->getSlidesTalkId() !== null) {
			return [];
		}
		$actions = $this->database->fetchPairsStringString(
			'SELECT l.locale, t.action FROM talks t JOIN locales l ON t.key_locale = l.id_locale WHERE t.key_translation_group = ? AND t.action IS NOT NULL AND t.key_talk_slides IS NULL',
			$talk->getTranslationGroupId(),
		);
		return array_map(fn($action) => ['name' => $action], $actions);
	}

}
