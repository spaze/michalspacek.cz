<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\EasterEgg\CrLfUrlInjections;
use MichalSpacekCz\Http\ContentSecurityPolicy\CspValues;
use MichalSpacekCz\Http\FetchMetadata\ResourceIsolationPolicy;
use MichalSpacekCz\Http\SecurityHeaders;
use Nette\Application\Application;
use Nette\Http\IRequest;
use Nette\Http\IResponse;

final readonly class WebApplication
{

	public function __construct(
		private IRequest $httpRequest,
		private IResponse $httpResponse,
		private SecurityHeaders $securityHeaders,
		private Application $application,
		private CrLfUrlInjections $crLfUrlInjections,
		private ResourceIsolationPolicy $resourceIsolationPolicy,
		private string $fqdn,
	) {
	}


	public function run(): void
	{
		$this->detectCrLfUrlInjectionAttempt();
		$this->redirectToSecure();
		$this->resourceIsolationPolicy->install();
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


	private function detectCrLfUrlInjectionAttempt(): void
	{
		if ($this->crLfUrlInjections->detectAttempt()) {
			exit();
		}
	}

}
