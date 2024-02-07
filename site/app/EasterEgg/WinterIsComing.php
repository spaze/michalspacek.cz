<?php
declare(strict_types = 1);

namespace MichalSpacekCz\EasterEgg;

use MichalSpacekCz\ShouldNotHappenException;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Presenter;
use Nette\Forms\Controls\TextInput;
use Nette\Utils\Arrays;
use Nette\Utils\Strings;

class WinterIsComing
{

	private const EMAILS = [
		'winter@example.com',
		'sample@email.tst',
		'arachni@email.gr',
	];

	private const HOSTS = [
		'burpcollaborator.net',
		'mailrez.com',
		'mailto.plus',
		'ourtimesupport.com',
		'ssemarketing.net',
	];

	private const STREETS = [
		'34 Watts road',
	];


	/**
	 * @return callable(TextInput): true
	 */
	public function ruleEmail(): callable
	{
		return function (TextInput $input) {
			if (
				is_string($input->getValue())
				&& (
					Arrays::contains(self::EMAILS, $input->getValue())
					|| Strings::match($input->getValue(), '/@(' . implode('|', array_map('preg_quote', self::HOSTS)) . ')$/') !== null
				)
			) {
				$this->sendSyntaxError($input);
			}
			return true;
		};
	}


	/**
	 * @return callable(TextInput): true
	 */
	public function ruleStreet(): callable
	{
		return function (TextInput $input) {
			if (Arrays::contains(self::STREETS, $input->getValue())) {
				$this->sendSyntaxError($input);
			}
			return true;
		};
	}


	private function sendSyntaxError(TextInput $input): never
	{
		$presenter = $input->form->getParent();
		if (!$presenter instanceof Presenter) {
			throw new ShouldNotHappenException(sprintf("This text input's form parent should be a %s but it's a %s", Presenter::class, get_debug_type($presenter)));
		}
		$presenter->sendResponse(new TextResponse(file_get_contents(__DIR__ . '/templates/sqlSyntaxError.html')));
	}

}
