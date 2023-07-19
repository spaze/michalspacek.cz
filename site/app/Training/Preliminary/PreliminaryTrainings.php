<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Preliminary;

use Contributte\Translation\Translator;
use MichalSpacekCz\Training\Applications\TrainingApplicationSources;
use MichalSpacekCz\Training\Dates\UpcomingTrainingDates;
use MichalSpacekCz\Training\Statuses;
use Nette\Database\Explorer;
use Nette\Database\Row;
use Spaze\Encryption\Symmetric\StaticKey;

class PreliminaryTrainings
{

	public function __construct(
		private readonly Explorer $database,
		private readonly UpcomingTrainingDates $upcomingTrainingDates,
		private readonly TrainingApplicationSources $trainingApplicationSources,
		private readonly StaticKey $emailEncryption,
		private readonly Translator $translator,
	) {
	}


	/**
	 * @return Row[]
	 */
	public function getPreliminary(): array
	{
		$trainings = [];
		$result = $this->database->fetchAll(
			'SELECT
				t.id_training AS idTraining,
				ua.action,
				t.name
			FROM trainings t
				JOIN training_applications a ON a.key_training = t.id_training
				JOIN training_application_status s ON a.key_status = s.id_status
				JOIN training_url_actions ta ON t.id_training = ta.key_training
				JOIN url_actions ua ON ta.key_url_action = ua.id_url_action
				JOIN languages l ON ua.key_language = l.id_language
			WHERE
				a.key_date IS NULL
				AND s.status != ?
				AND l.language = ?',
			Statuses::STATUS_CANCELED,
			$this->translator->getDefaultLocale(),
		);
		foreach ($result as $row) {
			$row->name = $this->translator->translate($row->name);
			$row->applications = [];
			$trainings[$row->idTraining] = $row;
		}

		$applications = $this->database->fetchAll(
			'SELECT
				a.id_application AS id,
				a.key_training AS idTraining,
				a.name,
				a.email,
				a.company,
				s.status,
				a.status_time AS statusTime,
				a.note,
				a.price,
				a.vat_rate AS vatRate,
				a.price_vat AS priceVat,
				a.invoice_id AS invoiceId,
				a.paid,
				sr.name AS sourceName
			FROM
				training_applications a
				JOIN training_application_status s ON a.key_status = s.id_status
				JOIN training_application_sources sr ON a.key_source = sr.id_source
			WHERE
				a.key_date IS NULL
				AND s.status != ?',
			Statuses::STATUS_CANCELED,
		);

		if ($applications) {
			foreach ($applications as $row) {
				if ($row->email) {
					$row->email = $this->emailEncryption->decrypt($row->email);
				}
				$row->sourceNameInitials = $this->trainingApplicationSources->getSourceNameInitials($row->sourceName);
				$trainings[$row->idTraining]->applications[] = $row;
			}
		}

		return $trainings;
	}


	/**
	 * @return int[]
	 */
	public function getPreliminaryCounts(): array
	{
		$upcoming = array_keys($this->upcomingTrainingDates->getPublicUpcoming());

		$total = $dateSet = 0;
		foreach ($this->getPreliminary() as $training) {
			if (in_array($training->action, $upcoming)) {
				$dateSet += count($training->applications);
			}
			$total += count($training->applications);
		}

		return [$total, $dateSet];
	}


	/**
	 * @return Row[]
	 */
	public function getPreliminaryWithDateSet(): array
	{
		$upcoming = array_keys($this->upcomingTrainingDates->getPublicUpcoming());

		$applications = [];
		foreach ($this->getPreliminary() as $training) {
			if (in_array($training->action, $upcoming)) {
				foreach ($training->applications as $application) {
					$application->training = $training;
					$applications[] = $application;
				}
			}
		}
		return $applications;
	}

}
