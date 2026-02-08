<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\SecurityHeaders;

use MichalSpacekCz\Application\Locale\LocaleLinkGenerator;
use MichalSpacekCz\Utils\Exceptions\UrlOriginNoHostException;
use MichalSpacekCz\Utils\UrlOrigin;
use Nette\Application\UI\InvalidLinkException;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Tracy\Debugger;
use Uri\WhatWg\Url;

final readonly class CrossOriginResourceSharing
{

	public const string HEADER_NAME_ALLOW_ORIGIN = 'Access-Control-Allow-Origin';


	public function __construct(
		private IRequest $httpRequest,
		private IResponse $httpResponse,
		private LocaleLinkGenerator $localeLinkGenerator,
		private UrlOrigin $urlOrigin,
	) {
	}


	/**
	 * Generates Access-Control-Allow-Origin header, if there's an Origin request header, and it matches any source link.
	 *
	 * @param string $source URL to allow in format "[[[module:]presenter:]action] [#fragment]"
	 */
	public function accessControlAllowOrigin(string $source): void
	{
		$origin = $this->httpRequest->getHeader('Origin');
		if ($origin === null) {
			return;
		}
		try {
			foreach ($this->localeLinkGenerator->allLinks($source) as $url) {
				$allowedOrigin = $this->urlOrigin->getFromUrl(new Url($url));
				if ($allowedOrigin !== null && $allowedOrigin === $origin) {
					$this->httpResponse->setHeader(self::HEADER_NAME_ALLOW_ORIGIN, $origin);
					break;
				}
			}
		} catch (InvalidLinkException | UrlOriginNoHostException $e) {
			Debugger::log($e);
			return;
		}
	}

}
