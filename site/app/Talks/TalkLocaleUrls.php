<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks;

use Nette\Database\Explorer;

class TalkLocaleUrls
{

	public function __construct(
		private readonly Explorer $database,
	) {
	}


	/**
	 * @return array<string, string> locale => action
	 */
	public function get(Talk $talk): array
	{
		if (!$talk->getTranslationGroupId()) {
			return [];
		}
		return $this->database->fetchPairs(
			'SELECT l.locale, t.action FROM talks t JOIN locales l ON t.key_locale = l.id_locale WHERE t.key_translation_group = ?',
			$talk->getTranslationGroupId(),
		);
	}

}