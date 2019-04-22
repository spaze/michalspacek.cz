<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use Nette\ComponentModel\IContainer;

class TrainingFile extends ProtectedForm
{

	public function __construct(IContainer $parent, string $name)
	{
		parent::__construct($parent, $name);

		$this->addUpload('file', 'Soubor:');
		$this->addSubmit('submit', 'Přidat');
	}

}
