<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use Nette\Application\UI\Form;

final class UnprotectedFormFactory
{

	public function create(): Form
	{
		return new Form();
	}

}
