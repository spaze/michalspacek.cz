<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

/**
 * Regenerate tokens form.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class RegenerateTokens extends ProtectedForm
{

	/**
	 * @param \Nette\ComponentModel\IContainer $parent
	 * @param string $name
	 */
	public function __construct(\Nette\ComponentModel\IContainer $parent, string $name)
	{
		parent::__construct($parent, $name);

		$this->addCheckbox('session', 'Session id')->setDefaultValue(true);
		$this->addCheckbox('permanent', 'Permanent login token')->setDefaultValue(true);
		$this->addCheckbox('returning', 'Returning user token')->setDefaultValue(true);
		$this->addSubmit('regenerate', 'Přegenerovat');
	}

}
