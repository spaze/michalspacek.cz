<?php
declare(strict_types = 1);

namespace MichalSpacekCz\EasterEgg\WinterIsComing;

use Composer\Pcre\Preg;
use Composer\Pcre\Regex;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Utils\Sleep;
use MichalSpacekCz\Utils\Strings;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Presenter;
use Nette\Forms\Control;
use Nette\Forms\Controls\TextInput;
use Nette\Utils\Arrays;

final readonly class WinterIsComing
{

	private const array EMAILS = [
		'sample@email.tst',
		'arachni@email.gr',
	];

	private const array HOSTS_REGEXPS = [
		'example\.com',
		'burpcollaborator\.net',
		'mailrez\.com',
		'mailto\.plus',
		'ourtimesupport\.com',
		'ssemarketing\.net',
	];

	private const array STREETS = [
		'34 Watts road',
	];


	public function __construct(
		private Sleep $sleep,
		private Strings $strings,
	) {
	}


	/**
	 * @return callable(Control): true
	 */
	public function ruleName(): callable
	{
		// Strings longer than x containing only word characters (no spaces, punctuation, etc.) are sus
		return function (Control $input) {
			if (!$input instanceof TextInput) {
				return true;
			}
			if (
				is_string($input->getValue())
				&& count(Preg::split('/\W/', $input->getValue())) === 1
				&& $this->strings->length($input->getValue()) > 10
			) {
				$this->sendSyntaxError($input);
			}
			return true;
		};
	}


	/**
	 * @return callable(Control): true
	 */
	public function ruleEmail(): callable
	{
		return function (Control $input) {
			if (!$input instanceof TextInput) {
				return true;
			}
			$value = $input->getValue();
			if (
				is_string($value)
				&& (
					Arrays::contains(self::EMAILS, $value)
					|| Regex::isMatch('/@(' . implode('|', self::HOSTS_REGEXPS) . ')$/', $value)
				)
			) {
				$this->sendSyntaxError($input);
			}
			return true;
		};
	}


	/**
	 * @return callable(Control): true
	 */
	public function ruleStreet(): callable
	{
		return function (Control $input) {
			if (!$input instanceof TextInput) {
				return true;
			}
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
		$this->sleep->randomSleep(5, 20);
		$presenter->sendResponse(new TextResponse(file_get_contents(__DIR__ . '/sqlSyntaxError.html')));
	}

}
