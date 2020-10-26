<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use Nette\ComponentModel\IContainer;

class TrainingInvoice extends ProtectedForm
{

	public function __construct(IContainer $parent, string $name, TrainingControlsFactory $trainingControlsFactory)
	{
		parent::__construct($parent, $name);
		$this->addText('invoice', 'Faktura:')
			->setRequired('Zadejte prosím číslo faktury');
		$trainingControlsFactory->addPaidDate($this, 'paid', 'Zaplaceno:', true);
		$this->addSubmit('submit', 'Zaplaceno');
	}

}
