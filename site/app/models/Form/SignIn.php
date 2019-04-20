<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

class SignIn extends ProtectedForm
{

	use Controls\SignIn;


	public function __construct(\Nette\ComponentModel\IContainer $parent, string $name)
	{
		parent::__construct($parent, $name);
		$this->addSignIn($this);
	}

}
