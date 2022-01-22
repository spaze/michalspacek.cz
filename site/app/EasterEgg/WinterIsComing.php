<?php
declare(strict_types = 1);

namespace MichalSpacekCz\EasterEgg;

use Nette\Application\AbortException;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Presenter;
use Nette\Forms\Controls\TextInput;
use Nette\Utils\Arrays;
use Nette\Utils\Strings;

class WinterIsComing
{

	/** @var array<int, string> */
	private array $emails = [
		'winter@example.com',
		'sample@email.tst',
		'arachni@email.gr',
	];

	/** @var array<int, string> */
	private array $hosts = [
		'burpcollaborator.net',
		'mailrez.com',
		'ssemarketing.net',
	];

	/** @var array<int, string> */
	private array $streets = [
		'34 Watts road',
	];


	public function ruleEmail(): callable
	{
		return function (TextInput $input) {
			if (
				Arrays::contains($this->emails, $input->getValue())
				|| Strings::match($input->getValue(), '/@(' . implode('|', array_map('preg_quote', $this->hosts)) . ')$/')
			) {
				$this->sendSyntaxError($input);
			}
			return true;
		};
	}


	public function ruleStreet(): callable
	{
		return function (TextInput $input) {
			if (Arrays::contains($this->streets, $input->getValue())) {
				$this->sendSyntaxError($input);
			}
			return true;
		};
	}


	/**
	 * @param TextInput $input
	 * @throws AbortException
	 */
	private function sendSyntaxError(TextInput $input): void
	{
		/** @var Presenter $presenter */
		$presenter = $input->getForm()->getParent();
		$presenter->sendResponse(new TextResponse(file_get_contents(__DIR__ . '/templates/sqlSyntaxError.latte')));
	}

}
