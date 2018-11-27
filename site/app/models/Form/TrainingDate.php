<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Training;

/**
 * Training date form.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrainingDate extends ProtectedForm
{

	use Controls\TrainingNote;

	private const STANDARD = 'Standardní';
	private const CUSTOM = 'Na zakázku';
	private const REPLACED = 'Nahrazené';
	private const DISCONTINUED = 'Ukončené';

	/** @var \MichalSpacekCz\Training\Trainings */
	protected $trainings;

	/** @var \MichalSpacekCz\Training\Dates */
	protected $trainingDates;

	/** @var \MichalSpacekCz\Training\Venues */
	protected $trainingVenues;


	public function __construct(
		\Nette\ComponentModel\IContainer $parent,
		string $name,
		Training\Trainings $trainings,
		Training\Dates $trainingDates,
		Training\Venues $trainingVenues
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
			$venues[$venue->id] = $venue->name;
		}
		$this->addSelect('venue', 'Místo:', $venues)
			->setRequired('Vyberte prosím místo');

		$this->addText('start', 'Začátek:')
			->setAttribute('placeholder', 'YYYY-MM-DD HH:MM nebo DD.MM.YYYY HH:MM')
			->setAttribute('title', 'Formát YYYY-MM-DD HH:MM nebo DD.MM.YYYY HH:MM')
			->setRequired('Zadejte prosím začátek')
			->addRule(self::PATTERN, 'Začátek musí být ve formátu YYYY-MM-DD HH:MM nebo DD.MM.YYYY HH:MM', '(\d{4}-\d{1,2}-\d{1,2} \d{1,2}:\d{2})|(\d{1,2}\.\d{1,2}\.\d{4} \d{1,2}:\d{2})');

		$this->addText('end', 'Konec:')
			->setAttribute('placeholder', 'YYYY-MM-DD HH:MM nebo DD.MM.YYYY HH:MM')
			->setAttribute('title', 'Formát YYYY-MM-DD HH:MM nebo DD.MM.YYYY HH:MM')
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

		$cooperations = array('' => 'žádná');
		foreach ($this->trainings->getCooperations() as $cooperation) {
			$cooperations[$cooperation->id] = $cooperation->name;
		}
		$this->addSelect('cooperation', 'Spolupráce:', $cooperations);
		$this->addNote($this);

		$this->addSubmit('submit', 'Přidat');
	}


	public function setTrainingDate(\Nette\Database\Row $date): self
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
