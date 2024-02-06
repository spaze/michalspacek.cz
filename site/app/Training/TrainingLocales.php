<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use MichalSpacekCz\Application\Locale\LocaleLinkGenerator;
use Nette\Database\Explorer;

readonly class TrainingLocales
{

	public function __construct(
		private Explorer $database,
		private LocaleLinkGenerator $localeLinkGenerator,
	) {
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
			$action,
		);
	}


	/**
	 * Translated locale parameters for trainings.
	 *
	 * @param array<string, string|null> $defaultParams
	 * @return array<string, array<string, string|null>>
	 */
	public function getLocaleLinkParams(?string $trainingAction, array $defaultParams): array
	{
		if ($trainingAction === null) {
			return $this->localeLinkGenerator->defaultParams($defaultParams);
		}
		$params = [];
		foreach ($this->getLocaleActions($trainingAction) as $key => $value) {
			$params[$key] = ['name' => $value];
		}
		return $params;
	}

}
