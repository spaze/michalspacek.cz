<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks;

use MichalSpacekCz\Database\TypedDatabase;

readonly class TalkLocaleUrls
{

	public function __construct(
		private TypedDatabase $database,
	) {
	}


	/**
	 * @return array<string, string> locale => action
	 */
	public function get(Talk $talk): array
	{
		if ($talk->getTranslationGroupId() === null) {
			return [];
		}
		return $this->database->fetchPairsStringString(
			'SELECT l.locale, t.action FROM talks t JOIN locales l ON t.key_locale = l.id_locale WHERE t.key_translation_group = ?',
			$talk->getTranslationGroupId(),
		);
	}

}
