<?php
namespace MichalSpacekCz\Form;

/**
 * Training invoice form.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrainingInvoice extends TrainingForm
{

	public function __construct(\Nette\ComponentModel\IContainer $parent, $name, \Nette\Localization\ITranslator $translator)
	{
		\Nette\Application\UI\Form::__construct($parent, $name, $translator);
		$this->addProtection('Platnost formuláře vypršela, odešlete jej znovu');

		$this->addText('invoice', 'Faktura:')
			->setRequired('Zadejte prosím číslo faktury');
		$this->addPaidDate('paid', 'Zaplaceno:', true);
		$this->addSubmit('submit', 'Zaplaceno');
	}

}
