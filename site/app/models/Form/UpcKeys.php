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
			->setAttribute('placeholder', 'UPC1234567')
			->setDefaultValue($ssid)
			->setRequired('Please enter an SSID')
			->addRule(Form::PATTERN, 'SSID has to start with "UPC"', '\s*' . $upcKeys->getSsidPattern());
		$this->addSubmit('submit', 'Get keys')
			->setHtmlId('submit')
			->setAttribute('data-alt', 'Wait…');
	}

}
