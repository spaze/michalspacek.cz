<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\EasterEgg\PhpInfoCookieSanitization;
use MichalSpacekCz\Http\Cookies\CookieName;
use MichalSpacekCz\Http\Cookies\Cookies;
use Nette\Http\Session;
use Spaze\PhpInfo\PhpInfo;

readonly class SanitizedPhpInfo
{

	public function __construct(
		private PhpInfo $phpInfo,
		private Session $sessionHandler,
		private Cookies $cookies,
	) {
	}


	public function getHtml(): string
	{
		// Session id is sanitized by default but let's be explicit here
		$this->phpInfo->addSanitization($this->sessionHandler->getId(), PhpInfoCookieSanitization::SESSION_ID);

		// Sanitize these as well even though they're sent to sign-in URL only
		$cookieNames = [
			CookieName::ReturningUser,
			CookieName::PermanentLogin,
		];
		foreach ($cookieNames as $cookieName) {
			$cookie = $this->cookies->getString($cookieName);
			if ($cookie !== null) {
				$this->phpInfo->addSanitization($cookie, PhpInfoCookieSanitization::COOKIE_VALUE);
			}
		}
		return $this->phpInfo->getHtml();
	}

}
