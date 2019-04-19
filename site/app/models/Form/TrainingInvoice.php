<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use Nette\ComponentModel\IContainer;
use Nette\Localization\ITranslator;

/**
 * Training invoice form.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrainingInvoice extends ProtectedForm
{

	use Controls\PaidDate;

	/** @var ITranslator */
	protected $translator;


	public function __construct(IContainer $parent, string $name, ITranslator $translator)
	{
		parent::__construct($parent, $name);
		$this->translator = $translator;

		$this->addText('invoice', 'Faktura:')
			->setRequired('Zadejte prosím číslo faktury');
		$this->addPaidDate('paid', 'Zaplaceno:', true);
		$this->addSubmit('submit', 'Zaplaceno');
	}

}
