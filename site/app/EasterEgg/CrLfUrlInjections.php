<?php
declare(strict_types = 1);

namespace MichalSpacekCz\EasterEgg;

use DateTimeImmutable;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Utils\Strings;

readonly class CrLfUrlInjections
{

	private const string COOKIE_NAME = 'crlfinjection';


	public function __construct(
		private IRequest $httpRequest,
		private IResponse $httpResponse,
	) {
	}


	public function detectAttempt(): bool
	{
		$url = $this->httpRequest->getUrl()->getAbsoluteUrl();
		if (!str_contains($url, "\r") && !str_contains($url, "\n")) {
			return false;
		}
		$matches = Strings::matchAll($url, sprintf('/Set\-Cookie:%s=([a-z0-9]+)/i', self::COOKIE_NAME));
		foreach ($matches as $match) {
			// Don't use any cookie name from the request to avoid e.g. session fixation
			$this->httpResponse->setCookie(
				self::COOKIE_NAME,
				$match[1],
				new DateTimeImmutable('-3 years 1 month 3 days 3 hours 7 minutes'),
				'/expired=3years/1month/3days/3hours/7minutes/ago',
			);
		}
		$this->httpResponse->setCode(IResponse::S204_NoContent, 'U WOT M8');
		$this->httpResponse->setHeader('Hack-the-Planet', 'https://youtu.be/u3CKgkyc7Qo?t=20');
		return true;
	}

}
