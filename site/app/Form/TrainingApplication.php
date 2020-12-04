<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Training\Dates;
use Nette\Application\UI\Presenter;
use Nette\Database\Row;
use Nette\Http\SessionSection;
use Nette\Localization\Translator;
use Nette\Utils\Html;
use Netxten\Forms\Controls\HiddenFieldWithLabel;

class TrainingApplication extends ProtectedForm
{

	protected Translator $translator;

	protected Dates $trainingDates;


	/**
	 * @param Presenter $parent
	 * @param string $name
	 * @param Row[] $dates
	 * @param Translator $translator
	 * @param TrainingControlsFactory $trainingControlsFactory
	 * @param Dates $trainingDates
	 */
	public function __construct(
		Presenter $parent,
		string $name,
		array $dates,
		Translator $translator,
		TrainingControlsFactory $trainingControlsFactory,
		Dates $trainingDates
	) {
		parent::__construct($parent, $name);
		$this->translator = $translator;
		$this->trainingDates = $trainingDates;

		$inputDates = array();
		$multipleDates = count($dates) > 1;
		foreach ($dates as $date) {
			$el = Html::el()->setText($this->trainingDates->formatDateVenueForUser($date));
			if ($date->label) {
				if ($multipleDates) {
					$el->addText(" [{$date->label}]");
				} else {
					$el->addHtml(Html::el('small', ['class' => 'label'])->setText($date->label));
				}
			}
			$inputDates[$date->dateId] = $el;
		}

		$label = 'Termín školení:';
		// trainingId is actually dateId, oh well
		if ($multipleDates) {
			$this->addSelect('trainingId', $label, $inputDates)
				->setRequired('Vyberte prosím termín a místo školení')
				->setPrompt('- vyberte termín a místo -')
				->addRule(self::INTEGER);
		} else {
			/** @var string $key */
			$key = key($inputDates);
			$field = new HiddenFieldWithLabel($label, $key, $inputDates[$key]);
			$field->addRule(self::INTEGER);
			$this->addComponent($field, 'trainingId');
		}

		$trainingControlsFactory->addAttendee($this);
		$trainingControlsFactory->addCompany($this);
		$trainingControlsFactory->addNote($this);
		$trainingControlsFactory->addCountry($this);

		$this->addSubmit('signUp', 'Odeslat');
	}


	/**
	 * @param SessionSection<string> $application
	 * @return static
	 */
	public function setApplicationFromSession(SessionSection $application): self
	{
		$values = array(
			'name' => $application->name,
			'email' => $application->email,
			'company' => $application->company,
			'street' => $application->street,
			'city' => $application->city,
			'zip' => $application->zip,
			'country' => $application->country,
			'companyId' => $application->companyId,
			'companyTaxId' => $application->companyTaxId,
			'note' => $application->note,
		);
		$this->setDefaults($values);

		if (!empty($application->country)) {
			$message = "messages.label.taxid.{$application->country}";
			$caption = $this->translator->translate($message);
			if ($caption !== $message) {
				$this->getComponent('companyTaxId')->caption = "{$caption}:";
			}
		}

		return $this;
	}

}
