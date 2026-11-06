<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Forms;

use MichalSpacekCz\SecurityTxtValidator\LambdaVersionCheck\SecurityTxtValidatorLambdaVersionCheck;
use Nette\Application\UI\Form;

final readonly class CheckLambdaVersionFormFactory
{

	public function __construct(
		private SecurityTxtValidatorLambdaVersionCheck $lambdaVersionCheck,
	) {
	}


	/**
	 * @param callable(bool): void $onSuccess
	 */
	public function create(callable $onSuccess): Form
	{
		$form = new Form();
		$form->addSubmit('check', 'Re-check');
		$form->onSuccess[] = function () use ($onSuccess): void {
			$matches = $this->lambdaVersionCheck->check();
			$onSuccess($matches);
		};
		return $form;
	}

}
