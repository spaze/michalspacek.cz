<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\UpcKeys\Technicolor;
use MichalSpacekCz\UpcKeys\UpcKeys;

class UpcKeysSsidFormFactory
{

	public function __construct(
		private readonly UnprotectedFormFactory $factory,
		private readonly UpcKeys $upcKeys,
		private readonly Technicolor $technicolor,
	) {
	}


	/**
	 * @param callable(string): void $onSuccess
	 * @param callable(): void $onError
	 */
	public function create(callable $onSuccess, callable $onError, ?string $ssid): UiForm
	{
		$form = $this->factory->create();
		$form->addText('ssid', 'SSID:')
			->setHtmlAttribute('placeholder', $this->upcKeys->getSsidPlaceholder())
			->setHtmlAttribute('title', '"UPC" and 7 digits')
			->setDefaultValue($ssid)
			->setRequired('Please enter an SSID')
			->addRule($form::Pattern, 'Wi-Fi network name has to be "UPC" and 7 digits (UPC1234567)', '\s*' . $this->upcKeys->getValidSsidPattern() . '\s*');
		$form->addSubmit('submit', 'Get keys')
			->setHtmlId('submit')
			->setHtmlAttribute('data-alt', 'Wait…');
		$form->onSuccess[] = function (UiForm $form) use ($onSuccess, $onError): void {
			$values = $form->getFormValues();
			$ssid = strtoupper(trim($values->ssid));
			if (!$this->technicolor->saveKeys($ssid)) {
				$onError();
			} else {
				$onSuccess($ssid);
			}
		};
		return $form;
	}

}
