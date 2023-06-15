<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\Http\ContentSecurityPolicy\CspValues;
use MichalSpacekCz\Http\SecurityHeaders;
use Nette\Application\Application;
use Nette\Http\IRequest;
use Nette\Http\IResponse;

class WebApplication
{

	public function __construct(
		private readonly IRequest $httpRequest,
		private readonly IResponse $httpResponse,
		private readonly SecurityHeaders $securityHeaders,
		private readonly Application $application,
		private readonly string $fqdn,
	) {
	}


	public function run(): void
	{
		$this->redirectToSecure();
		$this->application->onResponse[] = function (): void {
			$this->securityHeaders->sendHeaders();
		};
		$this->application->run();
	}


	private function redirectToSecure(): void
	{
		if (ServerEnv::tryGetString('HTTP_HOST') !== $this->fqdn) {
			$this->securityHeaders->sendHeaders(CspValues::Default);
			$url = $this->httpRequest->getUrl()->withScheme('https')->withHost($this->fqdn);
			$this->httpResponse->redirect($url->getAbsoluteUrl(), IResponse::S301_MovedPermanently);
			exit();
		}
	}

}
