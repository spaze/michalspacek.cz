<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\UpcKeys\UpcKeys as UpcKeysService;
use Nette\ComponentModel\IContainer;

class UpcKeys extends UnprotectedForm
{

	public function __construct(IContainer $parent, string $name, ?string $ssid, UpcKeysService $upcKeys)
	{
		parent::__construct($parent, $name);
		$this->addText('ssid', 'SSID:')
			->setHtmlAttribute('placeholder', $upcKeys->getSsidPlaceholder())
			->setHtmlAttribute('title', '"UPC" and 7 digits')
			->setDefaultValue($ssid)
			->setRequired('Please enter an SSID')
			->addRule(self::PATTERN, 'Wi-Fi network name has to be "UPC" and 7 digits (UPC1234567)', '\s*' . $upcKeys->getValidSsidPattern() . '\s*');
		$this->addSubmit('submit', 'Get keys')
			->setHtmlId('submit')
			->setHtmlAttribute('data-alt', 'Wait…');
	}

}
