<?php
declare(strict_types = 1);

namespace MichalSpacekCz\EasterEgg;

use Composer\Pcre\Regex;
use MichalSpacekCz\ShouldNotHappenException;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Presenter;
use Nette\Forms\Controls\TextInput;
use Nette\Utils\Arrays;

final class WinterIsComing
{

	private const array EMAILS = [
		'winter@example.com',
		'sample@email.tst',
		'arachni@email.gr',
	];

	private const array HOSTS = [
		'burpcollaborator.net',
		'mailrez.com',
		'mailto.plus',
		'ourtimesupport.com',
		'ssemarketing.net',
	];

	private const array STREETS = [
		'34 Watts road',
	];


	/**
	 * @return callable(TextInput): true
	 */
	public function ruleEmail(): callable
	{
		return function (TextInput $input) {
			$value = $input->getValue();
			if (
				is_string($value)
				&& (
					Arrays::contains(self::EMAILS, $value)
					|| Regex::isMatch('/@(' . implode('|', array_map('preg_quote', self::HOSTS)) . ')$/', $value)
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
