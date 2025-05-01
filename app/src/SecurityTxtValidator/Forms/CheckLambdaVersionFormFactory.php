<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Forms;

use MichalSpacekCz\Form\FormFactory;
use MichalSpacekCz\SecurityTxtValidator\LambdaVersionCheck\SecurityTxtValidatorLambdaVersionCheck;
use Nette\Forms\Form;

final readonly class CheckLambdaVersionFormFactory
{

	public function __construct(
		private FormFactory $factory,
		private SecurityTxtValidatorLambdaVersionCheck $lambdaVersionCheck,
	) {
	}


	/**
	 * @param callable(bool): void $onSuccess
	 */
	public function create(callable $onSuccess): Form
	{
		$form = $this->factory->create();
		$form->addSubmit('check', 'Re-check');
		$form->onSuccess[] = function () use ($onSuccess): void {
			$matches = $this->lambdaVersionCheck->check();
			$onSuccess($matches);
		};
		return $form;
	}

}
