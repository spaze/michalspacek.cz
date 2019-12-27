<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Training\Dates;
use MichalSpacekCz\Training\Trainings;
use MichalSpacekCz\Training\Venues;
use Nette\ComponentModel\IContainer;
use Nette\Database\Row;

class TrainingDate extends ProtectedForm
{

	private const STANDARD = 'Standardní';
	private const CUSTOM = 'Na zakázku';
	private const REPLACED = 'Nahrazené';
	private const DISCONTINUED = 'Ukončené';

	/** @var Trainings */
	protected $trainings;

	/** @var Dates */
	protected $trainingDates;

	/** @var Venues */
	protected $trainingVenues;


	public function __construct(
		IContainer $parent,
		string $name,
		Trainings $trainings,
		Dates $trainingDates,
		Venues $trainingVenues,
		TrainingControlsFactory $trainingControlsFactory
	) {
		parent::__construct($parent, $name);
		$this->trainings = $trainings;
		$this->trainingDates = $trainingDates;
		$this->trainingVenues = $trainingVenues;

		$trainings = array(
			self::STANDARD => [],
			self::CUSTOM => [],
			self::REPLACED => [],
			self::DISCONTINUED => [],
		);
		foreach ($this->trainings->getNamesIncludingCustomDiscontinued() as $training) {
			if ($training->discontinuedId !== null) {
				$key = self::DISCONTINUED;
			} elseif ($training->successorId !== null) {
				$key = self::REPLACED;
			} else {
				$key = ($training->custom ? self::CUSTOM : self::STANDARD);
			}
			$trainings[$key][$training->id] = $training->name;
		}
		$this->addSelect('training', 'Školení:', array_filter($trainings))
			->setRequired('Vyberte prosím školení');

		$venues = array();
		foreach ($this->trainingVenues->getAll() as $venue) {
			$venues[$venue->id] = "{$venue->name}, {$venue->city}";
		}
		$this->addSelect('venue', 'Místo:', $venues)
			->setRequired('Vyberte prosím místo');

		$this->addText('start', 'Začátek:')
			->setHtmlAttribute('placeholder', 'YYYY-MM-DD HH:MM nebo DD.MM.YYYY HH:MM')
			->setHtmlAttribute('title', 'Formát YYYY-MM-DD HH:MM nebo DD.MM.YYYY HH:MM')
			->setRequired('Zadejte prosím začátek')
			->addRule(self::PATTERN, 'Začátek musí být ve formátu YYYY-MM-DD HH:MM nebo DD.MM.YYYY HH:MM', '(\d{4}-\d{1,2}-\d{1,2} \d{1,2}:\d{2})|(\d{1,2}\.\d{1,2}\.\d{4} \d{1,2}:\d{2})');

		$this->addText('end', 'Konec:')
			->setHtmlAttribute('placeholder', 'YYYY-MM-DD HH:MM nebo DD.MM.YYYY HH:MM')
			->setHtmlAttribute('title', 'Formát YYYY-MM-DD HH:MM nebo DD.MM.YYYY HH:MM')
			->setRequired('Zadejte prosím konec')
			->addRule(self::PATTERN, 'Konec musí být ve formátu YYYY-MM-DD HH:MM nebo DD.MM.YYYY HH:MM', '(\d{4}-\d{1,2}-\d{1,2} \d{1,2}:\d{2})|(\d{1,2}\.\d{1,2}\.\d{4} \d{1,2}:\d{2})');

		$this->addText('label', 'Label:');

		$statuses = array();
		foreach ($this->trainingDates->getStatuses() as $status) {
			$statuses[$status->id] = $status->status;
		}
		$this->addSelect('status', 'Status:', $statuses)
			->setRequired('Vyberte prosím status');

		$this->addCheckbox('public', 'Veřejné:');

		$cooperations = array(0 => 'žádná');
		foreach ($this->trainings->getCooperations() as $cooperation) {
			$cooperations[$cooperation->id] = $cooperation->name;
		}
		$this->addSelect('cooperation', 'Spolupráce:', $cooperations)
			->addRule(self::INTEGER);
		$trainingControlsFactory->addNote($this);

		$this->addSubmit('submit', 'Přidat');
	}


	/**
	 * @param Row<mixed> $date
	 * @return $this
	 */
	public function setTrainingDate(Row $date): self
	{
		$values = array(
			'training' => $date->trainingId,
			'venue' => $date->venueId,
			'start' => $date->start->format('Y-m-d H:i'),
			'end' => $date->end->format('Y-m-d H:i'),
			'label' => $date->labelJson,
			'status' => $this->trainingDates->getStatusId($date->status),
			'public' => $date->public,
			'cooperation' => $date->cooperationId,
			'note' => $date->note,
		);
		$this->setDefaults($values);
		$this->getComponent('submit')->caption = 'Upravit';

		return $this;
	}

}
