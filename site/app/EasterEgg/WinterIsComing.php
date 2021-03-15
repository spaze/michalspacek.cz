<?php
declare(strict_types = 1);

namespace MichalSpacekCz\EasterEgg;

use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Presenter;
use Nette\Forms\Controls\TextInput;

class WinterIsComing
{

	/** @var array<integer, string> */
	private $emails = [
		'winter@example.com',
		'sample@email.tst',
		'arachni@email.gr',
	];


	public function rule(): callable
	{
		return function (TextInput $input) {
			if (in_array($input->getValue(), $this->emails, true)) {
				/** @var Presenter $presenter */
				$presenter = $input->getForm()->getParent();
				$presenter->sendResponse(new TextResponse(file_get_contents(__DIR__ . '/templates/sqlSyntaxError.latte')));
			}
			return true;
		};
	}

}
