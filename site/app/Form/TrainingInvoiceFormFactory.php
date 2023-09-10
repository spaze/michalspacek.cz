<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Form\Controls\TrainingControlsFactory;
use MichalSpacekCz\Training\Applications\TrainingApplications;

class TrainingInvoiceFormFactory
{

	public function __construct(
		private readonly FormFactory $factory,
		private readonly TrainingApplications $trainingApplications,
		private readonly TrainingControlsFactory $trainingControlsFactory,
	) {
	}


	/**
	 * @param callable(int): void $onSuccess
	 * @param callable(): void $onError
	 * @param list<int> $unpaidInvoiceIds
	 */
	public function create(callable $onSuccess, callable $onError, array $unpaidInvoiceIds): UiForm
	{
		$form = $this->factory->create();
		$form->addText('invoice', 'Faktura:')
			->setRequired('Zadejte prosím číslo faktury')
			->addRule($form::IS_IN, 'Zadejte číslo některé z nezaplacených faktur', $unpaidInvoiceIds);
		$this->trainingControlsFactory->addPaidDate($form->addText('paid', 'Zaplaceno:'), true);
		$form->addSubmit('submit', 'Zaplaceno');
		$form->onSuccess[] = function (UiForm $form) use ($onSuccess, $onError): void {
			$values = $form->getFormValues();
			$count = $this->trainingApplications->setPaidDate($values->invoice, $values->paid);
			if ($count === null) {
				$onError();
			} else {
				$onSuccess($count);
			}
		};
		return $form;
	}

}
