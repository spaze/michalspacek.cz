<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Company;

use Contributte\Translation\Translator;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Training\Dates\UpcomingTrainingDates;
use MichalSpacekCz\Training\Exceptions\CompanyTrainingDoesNotExistException;
use MichalSpacekCz\Training\Prices;
use Nette\Database\Explorer;
use Nette\Database\Row;
use Nette\Utils\Html;

class CompanyTrainings
{

	public function __construct(
		private readonly Explorer $database,
		private readonly TexyFormatter $texyFormatter,
		private readonly UpcomingTrainingDates $upcomingTrainingDates,
		private readonly Translator $translator,
		private readonly Prices $prices,
	) {
	}


	/**
	 * @throws CompanyTrainingDoesNotExistException
	 */
	public function getInfo(string $name): CompanyTraining
	{
		$result = $this->database->fetch(
			'SELECT
				t.id_training AS id,
				a.action,
				t.name,
				tc.description,
				t.content,
				tc.upsell,
				t.prerequisites,
				t.audience,
				t.capacity,
				tc.price,
				tc.alternative_duration_price AS alternativeDurationPrice,
				t.student_discount AS studentDiscount,
				t.materials,
				t.custom,
				tc.duration,
				tc.alternative_duration AS alternativeDuration,
				tc.alternative_duration_price_text AS alternativeDurationPriceText,
				t.key_successor AS successorId,
				t.key_discontinued AS discontinuedId
			FROM trainings t
				JOIN trainings_company tc ON t.id_training = tc.key_training
				JOIN training_url_actions ta ON t.id_training = ta.key_training
				JOIN url_actions a ON ta.key_url_action = a.id_url_action
				JOIN languages l ON a.key_language = l.id_language
			WHERE
				a.action = ?
				AND l.language = ?',
			$name,
			$this->translator->getDefaultLocale(),
		);
		if (!$result) {
			throw new CompanyTrainingDoesNotExistException($name);
		}
		return $this->createFromDatabaseRow($result);
	}


	/**
	 * @return array<string, Html> action => name
	 */
	public function getWithoutPublicUpcoming(): array
	{
		$result = $this->database->fetchAll(
			'SELECT
				a.action,
				t.name
			FROM trainings t
				JOIN trainings_company tc ON t.id_training = tc.key_training
				JOIN training_url_actions ta ON t.id_training = ta.key_training
				JOIN url_actions a ON ta.key_url_action = a.id_url_action
				JOIN languages l ON a.key_language = l.id_language
			WHERE
				t.key_successor IS NULL
				AND t.key_discontinued IS NULL
				AND l.language = ?
			ORDER BY t.order IS NULL, t.order',
			$this->translator->getDefaultLocale(),
		);
		$public = $this->upcomingTrainingDates->getPublicUpcoming();

		$trainings = [];
		foreach ($result as $training) {
			if (!is_string($training->action)) {
				throw new ShouldNotHappenException('Action should be a string but is ' . get_debug_type($training->action));
			}
			if (!isset($public[$training->action])) {
				$trainings[$training->action] = $this->texyFormatter->translate($training->name);
			}
		}

		return $trainings;
	}


	private function createFromDatabaseRow(Row $row): CompanyTraining
	{
		if (isset($row->alternativeDurationPriceText)) {
			$price = $this->prices->resolvePriceVat($row->alternativeDurationPrice);
			$alternativeDurationPriceText = $this->texyFormatter->translate($row->alternativeDurationPriceText, [
				$price->getPriceWithCurrency(),
				$price->getPriceVatWithCurrency(),
			]);
		}
		return new CompanyTraining(
			$row->id,
			$row->action,
			$this->texyFormatter->translate($row->name),
			$row->description ? $this->texyFormatter->translate($row->description) : null,
			$this->texyFormatter->translate($row->content),
			$row->upsell ? $this->texyFormatter->translate($row->upsell) : null,
			$row->prerequisites ? $this->texyFormatter->translate($row->prerequisites) : null,
			$row->audience ? $this->texyFormatter->translate($row->audience) : null,
			$row->capacity,
			$row->price,
			$row->alternativeDurationPrice,
			$row->studentDiscount,
			$row->materials ? $this->texyFormatter->translate($row->materials) : null,
			(bool)$row->custom,
			$this->texyFormatter->translate($row->duration),
			$this->texyFormatter->translate($row->alternativeDuration),
			$alternativeDurationPriceText ?? null,
			$row->successorId,
			$row->discontinuedId,
		);
	}

}
