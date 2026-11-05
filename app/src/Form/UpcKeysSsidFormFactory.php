<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\UpcKeys\UpcKeys;
use Nette\Application\UI\Form;

final readonly class UpcKeysSsidFormFactory
{

	public function __construct(
		private UpcKeys $upcKeys,
	) {
	}


	/**
	 * @param callable(string): void $onSuccess
	 */
	public function create(callable $onSuccess, ?string $ssid): Form
	{
		$form = new Form();
		$form->addText('ssid', 'SSID:')
			->setHtmlAttribute('placeholder', $this->upcKeys->getSsidPlaceholder())
			->setHtmlAttribute('title', '"UPC" and 7 digits')
			->setDefaultValue($ssid)
			->setRequired('Please enter an SSID')
			->addRule(Form::Pattern, 'Wi-Fi network name has to be "UPC" and 7 digits (UPC1234567)', '\s*' . $this->upcKeys->getValidSsidPattern() . '\s*');
		$form->addSubmit('submit', 'Get keys')
			->setHtmlId('submit');
		$form->onSuccess[] = function (Form $form) use ($onSuccess): void {
			$values = $form->getValues();
			assert(is_string($values->ssid));
			$ssid = strtoupper(trim($values->ssid));
			$onSuccess($ssid);
		};
		return $form;
	}

}
