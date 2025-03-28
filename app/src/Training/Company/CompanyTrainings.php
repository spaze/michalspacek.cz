<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Company;

use Contributte\Translation\Translator;
use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Training\Dates\UpcomingTrainingDates;
use MichalSpacekCz\Training\Exceptions\CompanyTrainingDoesNotExistException;
use MichalSpacekCz\Training\Prices;
use Nette\Database\Explorer;
use Nette\Database\Row;
use Nette\Utils\Html;

final readonly class CompanyTrainings
{

	public function __construct(
		private Explorer $database,
		private TypedDatabase $typedDatabase,
		private TexyFormatter $texyFormatter,
		private UpcomingTrainingDates $upcomingTrainingDates,
		private Translator $translator,
		private Prices $prices,
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
		if ($result === null) {
			throw new CompanyTrainingDoesNotExistException($name);
		}
		return $this->createFromDatabaseRow($result);
	}


	/**
	 * @return array<string, Html> action => name
	 */
	public function getWithoutPublicUpcoming(): array
	{
		$result = $this->typedDatabase->fetchAll(
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
			assert(is_string($training->action));
			assert(is_string($training->name));

			if (!isset($public[$training->action])) {
				$trainings[$training->action] = $this->texyFormatter->translate($training->name);
			}
		}

		return $trainings;
	}


	private function createFromDatabaseRow(Row $row): CompanyTraining
	{
		assert(is_int($row->id));
		assert(is_string($row->action));
		assert(is_string($row->name));
		assert(is_string($row->description));
		assert(is_string($row->content));
		assert(is_string($row->upsell));
		assert($row->prerequisites === null || is_string($row->prerequisites));
		assert($row->audience === null || is_string($row->audience));
		assert($row->capacity === null || is_int($row->capacity));
		assert(is_int($row->price));
		assert(is_int($row->alternativeDurationPrice));
		assert($row->studentDiscount === null || is_int($row->studentDiscount));
		assert($row->materials === null || is_string($row->materials));
		assert(is_int($row->custom));
		assert(is_string($row->duration));
		assert(is_string($row->alternativeDuration));
		assert(is_string($row->alternativeDurationPriceText));
		assert($row->successorId === null || is_int($row->successorId));
		assert($row->discontinuedId === null || is_int($row->discontinuedId));

		$price = $this->prices->resolvePriceVat($row->alternativeDurationPrice);
		$alternativeDurationPriceText = $this->texyFormatter->translate($row->alternativeDurationPriceText, [
			$price->getPriceWithCurrency(),
			$price->getPriceVatWithCurrency(),
		]);
		return new CompanyTraining(
			$row->id,
			$row->action,
			$this->texyFormatter->translate($row->name),
			$this->texyFormatter->translate($row->description),
			$this->texyFormatter->translate($row->content),
			$this->texyFormatter->translate($row->upsell),
			$row->prerequisites !== null ? $this->texyFormatter->translate($row->prerequisites) : null,
			$row->audience !== null ? $this->texyFormatter->translate($row->audience) : null,
			$row->capacity,
			$row->price,
			$row->alternativeDurationPrice,
			$row->studentDiscount,
			$row->materials !== null ? $this->texyFormatter->translate($row->materials) : null,
			(bool)$row->custom,
			$this->texyFormatter->translate($row->duration),
			$this->texyFormatter->translate($row->alternativeDuration),
			$alternativeDurationPriceText,
			$row->successorId,
			$row->discontinuedId,
		);
	}

}
