<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Form\Controls\TrainingAttendee;
use MichalSpacekCz\Form\Controls\TrainingCompany;
use MichalSpacekCz\Form\Controls\TrainingCountry;
use MichalSpacekCz\Form\Controls\TrainingNote;
use Nette\ComponentModel\IContainer;
use Nette\Database\Row;
use Nette\Http\SessionSection;
use Nette\Localization\ITranslator;
use Nette\Utils\Html;
use Netxten\Forms\Controls\HiddenFieldWithLabel;
use Netxten\Templating\Helpers;

class TrainingApplication extends ProtectedForm
{

	use TrainingAttendee;
	use TrainingCompany;
	use TrainingCountry;
	use TrainingNote;

	/** @var ITranslator */
	protected $translator;

	/** @var Helpers */
	protected $netxtenHelpers;

	/**
	 * @param IContainer $parent
	 * @param string $name
	 * @param Row[] $dates
	 * @param ITranslator $translator
	 * @param Helpers $netxtenHelpers
	 */
	public function __construct(
		IContainer $parent,
		string $name,
		array $dates,
		ITranslator $translator,
		Helpers $netxtenHelpers
	)
	{
		parent::__construct($parent, $name);
		$this->translator = $translator;
		$this->netxtenHelpers = $netxtenHelpers;

		$inputDates = array();
		$multipleDates = count($dates) > 1;
		foreach ($dates as $date) {
			$trainingDate = ($date->tentative ? $this->netxtenHelpers->localeIntervalMonth($date->start, $date->end) : $this->netxtenHelpers->localeIntervalDay($date->start, $date->end));
			$el = Html::el()->setText("{$trainingDate} {$date->venueCity}");
			if ($date->tentative) {
				$el->addText(' (' . $this->translator->translate('messages.label.tentativedate') . ')');
			}
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

		$this->addAttendee($this);
		$this->addCompany($this);
		$this->addNote($this);
		$this->addCountry($this);

		$this->addSubmit('signUp', 'Odeslat');
	}


	/**
	 * @param SessionSection $application
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
