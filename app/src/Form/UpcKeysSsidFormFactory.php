<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\UpcKeys\UpcKeys;
use Nette\Forms\Form;

readonly class UpcKeysSsidFormFactory
{

	public function __construct(
		private UnprotectedFormFactory $factory,
		private UpcKeys $upcKeys,
	) {
	}


	/**
	 * @param callable(string): void $onSuccess
	 */
	public function create(callable $onSuccess, ?string $ssid): UiForm
	{
		$form = $this->factory->create();
		$form->addText('ssid', 'SSID:')
			->setHtmlAttribute('placeholder', $this->upcKeys->getSsidPlaceholder())
			->setHtmlAttribute('title', '"UPC" and 7 digits')
			->setDefaultValue($ssid)
			->setRequired('Please enter an SSID')
			->addRule(Form::Pattern, 'Wi-Fi network name has to be "UPC" and 7 digits (UPC1234567)', '\s*' . $this->upcKeys->getValidSsidPattern() . '\s*');
		$form->addSubmit('submit', 'Get keys')
			->setHtmlId('submit')
			->setHtmlAttribute('data-alt', 'Wait…');
		$form->onSuccess[] = function (UiForm $form) use ($onSuccess): void {
			$values = $form->getFormValues();
			assert(is_string($values->ssid));
			$ssid = strtoupper(trim($values->ssid));
			$onSuccess($ssid);
		};
		return $form;
	}

}
