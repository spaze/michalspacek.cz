<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\Http\ContentSecurityPolicy\CspValues;
use MichalSpacekCz\Http\SecurityHeaders;
use Nette\Application\Application;
use Nette\Http\IResponse;

class WebApplication
{

	public function __construct(
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
		$uri = $_SERVER['REQUEST_URI'];
		if ($_SERVER['HTTP_HOST'] !== $this->fqdn) {
			$this->securityHeaders->sendHeaders(CspValues::Default);
			$this->httpResponse->redirect("https://{$this->fqdn}{$uri}", IResponse::S301_MovedPermanently);
			exit();
		}
	}

}
