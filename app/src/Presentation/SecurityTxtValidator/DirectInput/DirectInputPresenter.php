<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\SecurityTxtValidator\DirectInput;

use MichalSpacekCz\Presentation\SecurityTxtValidator\BasePresenter;
use MichalSpacekCz\SecurityTxtValidator\Forms\DirectInputFormFactory;
use MichalSpacekCz\SecurityTxtValidator\SecurityTxtValidator;
use MichalSpacekCz\SecurityTxtValidator\ValidationResult\ValidationResult;
use MichalSpacekCz\SecurityTxtValidator\ValidationResult\ValidationResultFactory;
use MichalSpacekCz\SecurityTxtValidator\ValidationResult\ValidationResultTemplateParameters;
use Nette\Forms\Form;

final class DirectInputPresenter extends BasePresenter
{

	private ValidationResultTemplateParameters $templateParameters;


	public function __construct(
		private readonly DirectInputFormFactory $formFactory,
		private readonly ValidationResultFactory $validationResultFactory,
		private readonly SecurityTxtValidator $securityTxtValidator,
	) {
		$this->templateParameters = new ValidationResultTemplateParameters();
		parent::__construct();
	}


	public function actionDefault(): void
	{
		$this->templateParameters->showIntro = true;
		$this->template->setParameters($this->templateParameters);
	}


	protected function createComponentValidate(): Form
	{
		return $this->formFactory->create(function (string $input): void {
			$this->templateParameters->showIntro = false;
			$this->securityTxtValidator->validateDirectInput($input, $this->templateParameters);
			$this->template->setParameters($this->templateParameters);
		});
	}


	protected function createComponentValidationResult(): ValidationResult
	{
		return $this->validationResultFactory->create($this->templateParameters);
	}

}
