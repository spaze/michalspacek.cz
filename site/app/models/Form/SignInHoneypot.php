<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Form\Controls\SignIn;
use Nette\ComponentModel\IContainer;

class SignInHoneypot extends UnprotectedForm
{

	use SignIn;


	public function __construct(IContainer $parent, string $name)
	{
		parent::__construct($parent, $name);
		$this->addSignIn($this);
	}

}
