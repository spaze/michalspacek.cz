<?php
declare(strict_types = 1);

namespace MichalSpacekCz\EasterEgg;

use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Presenter;
use Nette\Forms\Controls\TextInput;
use Nette\Utils\Arrays;
use Nette\Utils\Strings;

class WinterIsComing
{

	/** @var array<integer, string> */
	private array $emails = [
		'winter@example.com',
		'sample@email.tst',
		'arachni@email.gr',
	];

	/** @var array<integer, string> */
	private array $hosts = [
		'ssemarketing.net',
	];


	public function rule(): callable
	{
		return function (TextInput $input) {
			if (
				Arrays::contains($this->emails, $input->getValue())
				|| Strings::match($input->getValue(), '/@' . implode('|', array_map('preg_quote', $this->hosts)) . '$/')
			) {
				/** @var Presenter $presenter */
				$presenter = $input->getForm()->getParent();
				$presenter->sendResponse(new TextResponse(file_get_contents(__DIR__ . '/templates/sqlSyntaxError.latte')));
			}
			return true;
		};
	}

}
