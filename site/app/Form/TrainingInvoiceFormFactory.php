<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Form\Controls\TrainingControlsFactory;
use MichalSpacekCz\Training\Applications;
use Nette\Application\UI\Form;
use stdClass;

class TrainingInvoiceFormFactory
{

	public function __construct(
		private readonly FormFactory $factory,
		private readonly Applications $trainingApplications,
		private readonly TrainingControlsFactory $trainingControlsFactory,
	) {
	}


	/**
	 * @param callable(int): void $onSuccess
	 * @return Form
	 */
	public function create(callable $onSuccess): Form
	{
		$form = $this->factory->create();
		$form->addText('invoice', 'Faktura:')
			->setRequired('Zadejte prosím číslo faktury');
		$this->trainingControlsFactory->addPaidDate($form, 'paid', 'Zaplaceno:', true);
		$form->addSubmit('submit', 'Zaplaceno');
		$form->onSuccess[] = function (Form $form, stdClass $values) use ($onSuccess): void {
			$count = $this->trainingApplications->setPaidDate($values->invoice, $values->paid);
			$onSuccess($count);
		};
		return $form;
	}

}
