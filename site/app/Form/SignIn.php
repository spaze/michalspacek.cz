<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Form\Controls\SignIn as SignInControl;
use Nette\ComponentModel\IContainer;

class SignIn extends ProtectedForm
{

	use SignInControl;


	public function __construct(IContainer $parent, string $name)
	{
		parent::__construct($parent, $name);
		$this->addSignIn($this);
	}

}
