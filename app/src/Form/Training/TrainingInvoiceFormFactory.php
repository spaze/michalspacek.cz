<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Training;

use MichalSpacekCz\Form\Controls\TrainingControlsFactory;
use MichalSpacekCz\Training\Applications\TrainingApplications;
use Nette\Application\UI\Form;

final readonly class TrainingInvoiceFormFactory
{

	public function __construct(
		private TrainingApplications $trainingApplications,
		private TrainingControlsFactory $trainingControlsFactory,
	) {
	}


	/**
	 * @param callable(int): void $onSuccess
	 * @param callable(): void $onError
	 * @param list<int> $unpaidInvoiceIds
	 */
	public function create(callable $onSuccess, callable $onError, array $unpaidInvoiceIds): Form
	{
		$form = new Form();
		$form->addText('invoice', 'Faktura:')
			->setRequired('Zadejte prosím číslo faktury')
			->addRule(Form::IsIn, 'Zadejte číslo některé z nezaplacených faktur', $unpaidInvoiceIds);
		$this->trainingControlsFactory->addPaidDate($form->addText('paid', 'Zaplaceno:'), true);
		$form->addSubmit('submit', 'Zaplaceno');
		$form->onSuccess[] = function (Form $form) use ($onSuccess, $onError): void {
			$values = $form->getValues();
			assert(is_string($values->invoice));
			assert(is_string($values->paid));
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
