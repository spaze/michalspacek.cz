<?php
namespace MichalSpacekCz\Form;

/**
 * Training invoice form.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrainingInvoice extends Form
{

	/** @var \Nette\Localization\ITranslator */
	protected $translator;


	public function __construct(\Nette\ComponentModel\IContainer $parent, $name, \Nette\Localization\ITranslator $translator)
	{
		parent::__construct($parent, $name);
		$this->translator = $translator;

		$this->addProtection('Platnost formuláře vypršela, odešlete jej znovu');

		$this->addText('invoice', 'Faktura:')
			->setRequired('Zadejte prosím číslo faktury');
		$this->addPaidDate('paid', 'Zaplaceno:', true);
		$this->addSubmit('submit', 'Zaplaceno');
	}

}
