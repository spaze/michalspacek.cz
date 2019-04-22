<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use Nette\ComponentModel\IContainer;

class RegenerateTokens extends ProtectedForm
{

	public function __construct(IContainer $parent, string $name)
	{
		parent::__construct($parent, $name);

		$this->addCheckbox('session', 'Session id')->setDefaultValue(true);
		$this->addCheckbox('permanent', 'Permanent login token')->setDefaultValue(true);
		$this->addCheckbox('returning', 'Returning user token')->setDefaultValue(true);
		$this->addSubmit('regenerate', 'Přegenerovat');
	}

}
