<?php
namespace MichalSpacekCz\Form;

use Netxten\Templating\Helpers;

/**
 * Training application form.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
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
	 * @param array $dates
	 * @param \Nette\Localization\ITranslator $translator
	 * @param \Netxten\Templating\Helpers $netxtenHelpers
	 */
	public function __construct(
		\Nette\ComponentModel\IContainer $parent,
		$name,
		array $dates,
		\Nette\Localization\ITranslator $translator,
		\Netxten\Templating\Helpers $netxtenHelpers
	)
	{
		parent::__construct($parent, $name);
		$this->translator = $translator;
		$this->netxtenHelpers = $netxtenHelpers;

		$inputDates = array();
		foreach ($dates as $date) {
			$trainingDate = ($date->tentative ? $this->netxtenHelpers->localeIntervalMonth($date->start, $date->end) : $this->netxtenHelpers->localeIntervalDay($date->start, $date->end));
			$inputDates[$date->dateId] = "{$trainingDate} {$date->venueCity}" . ($date->tentative ? ' (' . $this->translator->translate('messages.label.tentativedate') . ')' : '');
		}

		$label = 'Termín školení:';
		// trainingId is actually dateId, oh well
		if (count($dates) > 1) {
			$this->addSelect('trainingId', $label, $inputDates)
				->setRequired('Vyberte prosím termín a místo školení')
				->setPrompt('- vyberte termín a místo -');
		} else {
			$field = new \Netxten\Forms\Controls\HiddenFieldWithLabel($label, key($inputDates), current($inputDates));
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
	 */
	public function setApplicationFromSession(\Nette\Http\SessionSection $application)
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
