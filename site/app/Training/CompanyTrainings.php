<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use MichalSpacekCz\Formatter\Texy;
use Nette\Database\Explorer;
use Nette\Database\Row;
use Nette\Localization\Translator;

class CompanyTrainings
{

	/** @var Explorer */
	protected $database;

	/** @var Texy */
	protected $texyFormatter;

	/** @var Dates */
	protected $trainingDates;

	/** @var Translator */
	protected $translator;


	public function __construct(Explorer $context, Texy $texyFormatter, Dates $trainingDates, Translator $translator)
	{
		$this->database = $context;
		$this->texyFormatter = $texyFormatter;
		$this->trainingDates = $trainingDates;
		$this->translator = $translator;
	}


	/**
	 * @param string $name
	 * @return Row<mixed>|null
	 */
	public function getInfo(string $name): ?Row
	{
		/** @var Row<mixed>|null $result */
		$result = $this->database->fetch(
			'SELECT
				t.id_training AS trainingId,
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
			$this->translator->getDefaultLocale()
		);

		return ($result ? $this->texyFormatter->formatTraining($result) : null);
	}


	/**
	 * @return Row[]
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
			$this->translator->getDefaultLocale()
		);
		$public = $this->trainingDates->getPublicUpcoming();

		$trainings = array();
		foreach ($result as $training) {
			if (!isset($public[$training->action])) {
				$trainings[$training->action] = $this->texyFormatter->formatTraining($training);
			}
		}

		return $trainings;
	}

}
