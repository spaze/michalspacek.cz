<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\ValidationResult;

use MichalSpacekCz\Application\UiControl;

final class ValidationResult extends UiControl
{

	public function __construct(
		private readonly ValidationResultTemplateParameters $templateParameters,
	) {
	}


	public function render(): void
	{
		$this->template->setParameters($this->templateParameters);
		$this->template->render(__DIR__ . '/validationResult.latte');
	}

}
