<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Preliminary;

use Contributte\Translation\Translator;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Training\Applications\TrainingApplication;
use MichalSpacekCz\Training\Applications\TrainingApplicationFactory;
use MichalSpacekCz\Training\ApplicationStatuses\TrainingApplicationStatuses;
use MichalSpacekCz\Training\Dates\UpcomingTrainingDates;
use Nette\Database\Explorer;
use ParagonIE\Halite\Alerts\HaliteAlert;
use SodiumException;

readonly class PreliminaryTrainings
{

	public function __construct(
		private Explorer $database,
		private UpcomingTrainingDates $upcomingTrainingDates,
		private TrainingApplicationFactory $trainingApplicationFactory,
		private Translator $translator,
	) {
	}


	/**
	 * @return list<PreliminaryTraining>
	 * @throws HaliteAlert
	 * @throws SodiumException
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
			TrainingApplicationStatuses::STATUS_CANCELED,
			$this->translator->getDefaultLocale(),
		);
		foreach ($result as $row) {
			$training = new PreliminaryTraining(
				$row->idTraining,
				$row->action,
				$row->name = $this->translator->translate($row->name),
			);
			$trainings[$training->getId()] = $training;
		}

		$applications = $this->database->fetchAll(
			'SELECT
				a.id_application AS id,
				a.name,
				a.email,
				a.familiar,
				a.company,
				a.street,
				a.city,
				a.zip,
				a.country,
				a.company_id AS companyId,
				a.company_tax_id AS companyTaxId,
				a.note,
				s.status,
				a.status_time AS statusTime,
				d.id_date AS dateId,
				t.id_training AS trainingId,
				ua.action AS trainingAction,
				t.name AS trainingName,
				d.start AS trainingStart,
				d.end AS trainingEnd,
				d.public AS publicDate,
				d.remote,
				d.remote_url AS remoteUrl,
				d.remote_notes AS remoteNotes,
				d.video_href AS videoHref,
				d.feedback_href AS feedbackHref,
				v.action AS venueAction,
				v.name AS venueName,
				v.name_extended AS venueNameExtended,
				v.address AS venueAddress,
				v.city AS venueCity,
				a.price,
				a.vat_rate AS vatRate,
				a.price_vat AS priceVat,
				a.discount,
				a.invoice_id AS invoiceId,
				a.paid,
				a.access_token AS accessToken,
				sr.alias AS sourceAlias,
				sr.name AS sourceName
			FROM
				training_applications a
				LEFT JOIN training_dates d ON a.key_date = d.id_date
				JOIN trainings t ON (d.key_training = t.id_training OR a.key_training = t.id_training)
				LEFT JOIN training_venues v ON d.key_venue = v.id_venue
				JOIN training_application_status s ON a.key_status = s.id_status
				JOIN training_application_sources sr ON a.key_source = sr.id_source
				JOIN training_url_actions ta ON t.id_training = ta.key_training
				JOIN url_actions ua ON ta.key_url_action = ua.id_url_action
				JOIN languages l ON ua.key_language = l.id_language
			WHERE
				a.key_date IS NULL
				AND s.status != ?
				AND l.language = ?',
			TrainingApplicationStatuses::STATUS_CANCELED,
			$this->translator->getDefaultLocale(),
		);

		foreach ($applications as $row) {
			$application = $this->trainingApplicationFactory->createFromDatabaseRow($row);
			$trainingId = $application->getTrainingId();
			if ($trainingId === null) {
				throw new ShouldNotHappenException();
			}
			$trainings[$trainingId]->addApplication($application);
		}
		return array_values($trainings);
	}


	/**
	 * @return array{0:int, 1:int} total, date exists for these
	 * @throws HaliteAlert
	 * @throws SodiumException
	 */
	public function getPreliminaryCounts(): array
	{
		$upcoming = array_keys($this->upcomingTrainingDates->getPublicUpcoming());

		$total = $dateSet = 0;
		foreach ($this->getPreliminary() as $training) {
			if (in_array($training->getAction(), $upcoming)) {
				$dateSet += count($training->getApplications());
			}
			$total += count($training->getApplications());
		}

		return [$total, $dateSet];
	}


	/**
	 * @return list<TrainingApplication>
	 * @throws HaliteAlert
	 * @throws SodiumException
	 */
	public function getPreliminaryWithDateSet(): array
	{
		$upcoming = array_keys($this->upcomingTrainingDates->getPublicUpcoming());

		$applications = [];
		foreach ($this->getPreliminary() as $training) {
			if (in_array($training->getAction(), $upcoming)) {
				foreach ($training->getApplications() as $application) {
					$applications[] = $application;
				}
			}
		}
		return $applications;
	}

}
