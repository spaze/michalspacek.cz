<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use Nette\Utils\Html;

class TrainingApplication extends ProtectedForm
{

	use Controls\TrainingAttendee;
	use Controls\TrainingCompany;
	use Controls\TrainingCountry;
	use Controls\TrainingNote;

	/** @var \Nette\Localization\ITranslator */
	protected $translator;

	/** @var \Netxten\Templating\Helpers */
	protected $netxtenHelpers;

	/**
	 * @param \Nette\ComponentModel\IContainer $parent
	 * @param string $name
	 * @param \Nette\Database\Row[] $dates
	 * @param \Nette\Localization\ITranslator $translator
	 * @param \Netxten\Templating\Helpers $netxtenHelpers
	 */
	public function __construct(
		\Nette\ComponentModel\IContainer $parent,
		string $name,
		array $dates,
		\Nette\Localization\ITranslator $translator,
		\Netxten\Templating\Helpers $netxtenHelpers
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
			$field = new \Netxten\Forms\Controls\HiddenFieldWithLabel($label, key($inputDates), current($inputDates));
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
	 * @param \Nette\Http\SessionSection $application
	 * @return static
	 */
	public function setApplicationFromSession(\Nette\Http\SessionSection $application): self
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
