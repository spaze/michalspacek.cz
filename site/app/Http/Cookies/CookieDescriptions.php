<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Cookies;

use MichalSpacekCz\Application\Theme;
use MichalSpacekCz\DateTime\DateTime;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\User\Manager;
use Nette\Http\Helpers;
use Nette\Http\Session;

class CookieDescriptions
{

	public function __construct(
		private readonly Manager $authenticator,
		private readonly Theme $theme,
		private readonly Session $sessionHandler,
		private readonly TexyFormatter $texyFormatter,
		private readonly DateTime $dateTime,
	) {
	}


	/**
	 * @return list<CookieDescription>
	 */
	public function get(): array
	{
		$options = $this->sessionHandler->getOptions();
		$cookieLifetime = $options['cookie_lifetime'];
		if (!is_int($cookieLifetime)) {
			throw new ShouldNotHappenException("The cookie_lifetime option should be an int, but it's a " . get_debug_type($cookieLifetime));
		}
		/** @noinspection PhpInternalEntityUsedInspection */
		return [
			new CookieDescription(
				CookieName::PermanentLogin->value,
				true,
				$this->texyFormatter->translate('messages.cookies.cookie.permanentLogin'),
				$this->dateTime->getDaysFromString($this->authenticator->getPermanentLoginCookieLifetime()),
			),
			new CookieDescription(
				CookieName::ReturningUser->value,
				true,
				$this->texyFormatter->translate('messages.cookies.cookie.returningUser'),
				$this->dateTime->getDaysFromString($this->authenticator->getReturningUserCookieLifetime()),
			),
			new CookieDescription(
				CookieName::Theme->value,
				false,
				$this->texyFormatter->translate('messages.cookies.cookie.theme'),
				$this->dateTime->getDaysFromString($this->theme->getCookieLifetime()),
			),
			new CookieDescription(
				$this->sessionHandler->getName(),
				false,
				$this->texyFormatter->translate('messages.cookies.cookie.netteSession'),
				$this->dateTime->getDaysFromString($cookieLifetime . ' seconds'),
			),
			new CookieDescription(
				Helpers::StrictCookieName,
				false,
				$this->texyFormatter->translate('messages.cookies.cookie.netteSameSiteCheck'),
				null,
			),
		];
	}

}
