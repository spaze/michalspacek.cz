<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use Contributte\Translation\Translator;
use MichalSpacekCz\Http\Cookies\CookieDescriptions;

class CookiesPresenter extends BasePresenter
{

	public function __construct(
		private readonly CookieDescriptions $cookieDescriptions,
		private readonly Translator $translator,
	) {
		parent::__construct();
	}


	public function renderDefault(): void
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.cookies');
		$cookies = $this->cookieDescriptions->get();
		$internalCookies = $publicCookies = [];
		foreach ($cookies as $cookie) {
			if ($cookie->isInternal()) {
				$internalCookies[] = $cookie;
			} else {
				$publicCookies[] = $cookie;
			}
		}
		$this->template->internalCookies = $internalCookies;
		$this->template->publicCookies = $publicCookies;
	}

}
