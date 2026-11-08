<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use Contributte\Translation\Translator;
use MichalSpacekCz\Application\Locale\LocaleLinkGenerator;
use Nette\Database\Explorer;
use Nette\Database\Row;

final readonly class TrainingLocales
{

	public function __construct(
		private Explorer $database,
		private LocaleLinkGenerator $localeLinkGenerator,
		private Translator $translator,
	) {
	}


	/**
	 * The URLs of a training in every locale it has one in. Two trainings can have the same URL in different languages,
	 * so the action names the training with that URL in $locale, the one a page in $locale shows, and only when there's
	 * none, the training with the lowest id that has it in another language, so a link written with another language's
	 * URL still finds its training. The query picks the training, so the URL is compared the way the database compares
	 * it when finding the training a page shows, where `PHP` is `php`.
	 *
	 * @return array<string, string> locale => action
	 */
	public function getLocaleActions(string $action, string $locale): array
	{
		$rows = $this->database->fetchAll(
			'SELECT
				ta.key_training AS trainingId,
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
				JOIN languages l ON a.key_language = l.id_language
				WHERE a.action = ?
				ORDER BY l.language = ? DESC, ta.key_training
				LIMIT 1
			)',
			$action,
			$locale,
		);
		return array_first($this->getActionsByTraining($rows)) ?? [];
	}


	/**
	 * Translated locale parameters for trainings.
	 *
	 * @param array<array-key, mixed> $defaultParams
	 * @return array<string, array<array-key, mixed>>
	 */
	public function getLocaleLinkParams(?string $trainingAction, array $defaultParams): array
	{
		if ($trainingAction === null) {
			return $this->localeLinkGenerator->defaultParams($defaultParams);
		}
		return $this->getLinkParams($this->getLocaleActions($trainingAction, $this->translator->getDefaultLocale()));
	}


	/**
	 * The parameters of every training's page in the locales it has one in, for a list of trainings to link each of
	 * them without asking for each one.
	 *
	 * @return array<int, array<string, array{name: string}>> training id => locale => ['name' => action]
	 */
	public function getAllLocaleLinkParams(): array
	{
		$rows = $this->database->fetchAll(
			'SELECT
				ta.key_training AS trainingId,
				l.language,
				a.action
			FROM
				url_actions a
				JOIN training_url_actions ta ON a.id_url_action = ta.key_url_action
				JOIN languages l ON a.key_language = l.id_language',
		);
		return array_map($this->getLinkParams(...), $this->getActionsByTraining($rows));
	}


	/**
	 * @param array<Row> $rows
	 * @return array<int, array<string, string>> training id => locale => action
	 */
	private function getActionsByTraining(array $rows): array
	{
		$trainings = [];
		foreach ($rows as $row) {
			assert(is_int($row->trainingId));
			assert(is_string($row->language));
			assert(is_string($row->action));
			$trainings[$row->trainingId][$row->language] = $row->action;
		}
		return $trainings;
	}


	/**
	 * @param array<string, string> $actions locale => action
	 * @return array<string, array{name: string}>
	 */
	private function getLinkParams(array $actions): array
	{
		return array_map(fn(string $action): array => ['name' => $action], $actions);
	}

}
