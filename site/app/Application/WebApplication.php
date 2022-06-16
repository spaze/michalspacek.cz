<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\Http\SecurityHeaders;
use Nette\Application\Application;
use Nette\Application\Request;
use Nette\Application\UI\Presenter;
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

		$this->application->onRequest[] = function (Application $sender, Request $request): void {
			$action = $request->getParameter(Presenter::ACTION_KEY) ?? Presenter::DEFAULT_ACTION;
			$this->securityHeaders->setCsp($request->getPresenterName(), $action);
		};
		$this->application->onResponse[] = function (): void {
			$this->securityHeaders->sendHeaders();
		};
		$this->application->run();
	}


	private function redirectToSecure(): void
	{
		$uri = $_SERVER['REQUEST_URI'];
		if ($_SERVER['HTTP_HOST'] !== $this->fqdn) {
			$this->securityHeaders->setDefaultCsp()->sendHeaders();
			$this->httpResponse->redirect("https://{$this->fqdn}{$uri}", IResponse::S301_MOVED_PERMANENTLY);
			exit();
		}
	}

}
