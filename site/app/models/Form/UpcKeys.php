<?php
namespace MichalSpacekCz\Form;

/**
 * UPC keys form.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class UpcKeys extends \Nette\Application\UI\Form
{

	/**
	 * @param \Nette\ComponentModel\IContainer $parent
	 * @param string $name
	 * @param string $ssid
	 */
	public function __construct(\Nette\ComponentModel\IContainer $parent, $name, $ssid, \MichalSpacekCz\UpcKeys $upcKeys)
	{
		parent::__construct($parent, $name);
		$this->addText('ssid', 'SSID:')
			->setAttribute('placeholder', $upcKeys->getSsidPlaceholder())
			->setDefaultValue($ssid)
			->setRequired('Please enter an SSID')
			->addRule(Form::PATTERN, 'Wi-Fi network name has to start with "UPC"', '\s*' . $upcKeys->getUpcSsidPattern());
		$this->addSubmit('submit', 'Get keys')
			->setHtmlId('submit')
			->setAttribute('data-alt', 'Wait…');
	}

}
