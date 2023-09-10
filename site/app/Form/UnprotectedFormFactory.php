<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

final class UnprotectedFormFactory
{

	public function create(): UiForm
	{
		return new UiForm();
	}

}
