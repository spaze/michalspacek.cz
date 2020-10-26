<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use Nette\Database\Context;

class Locales
{

	/** @var Context */
	protected $database;


	public function __construct(Context $context)
	{
		$this->database = $context;
	}


	/**
	 * Get localized training actions.
	 *
	 * @param string $action
	 * @return array<string, string>
	 */
	public function getLocaleActions(string $action): array
	{
		return $this->database->fetchPairs(
			'SELECT
				l.language,
				a.action
			FROM
				url_actions a
				JOIN training_url_actions ta ON a.id_url_action = ta.key_url_action
				JOIN languages l ON a.key_language = l.id_language
			WHERE ta.key_training = (
				SELECT ta.key_training
				FROM url_actions a
				JOIN training_url_actions ta ON a.id_url_action = ta.key_url_action
				WHERE a.action = ?
				LIMIT 1
			)',
			$action
		);
	}

}
