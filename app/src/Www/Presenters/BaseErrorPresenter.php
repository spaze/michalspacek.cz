<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use MichalSpacekCz\Application\AppRequest;
use MichalSpacekCz\Http\Redirections;
use MichalSpacekCz\ShouldNotHappenException;
use Nette\Application\Request;
use Nette\Http\IResponse;
use Override;
use Tracy\ILogger;

abstract class BaseErrorPresenter extends BasePresenter
{

	private Redirections $redirections;
	private ILogger $logger;
	private AppRequest $appRequest;
	protected bool $logAccess = true;


	/**
	 * @internal
	 */
	public function injectRedirections(Redirections $redirections): void
	{
		$this->redirections = $redirections;
	}


	/**
	 * @internal
	 */
	public function injectLogger(ILogger $logger): void
	{
		$this->logger = $logger;
	}


	/**
	 * @internal
	 */
	public function injectAppRequest(AppRequest $appRequest): void
	{
		$this->appRequest = $appRequest;
	}


	#[Override]
	public function startup(): void
	{
		parent::startup();
		$request = $this->getRequest();
		if ($request === null) {
			throw new ShouldNotHappenException('Request should be set before this method is called in UI\Presenter::run()');
		}
		if (!$request->isMethod(Request::FORWARD)) {
			$this->error();
		}

		$destination = $this->redirections->getDestination($this->getHttpRequest()->getUrl());
		if ($destination !== null) {
			$this->redirectUrl($destination, IResponse::S301_MovedPermanently);
		}

		if ($this->logAccess) {
			$e = $this->appRequest->getException($request);
			$this->logger->log("HTTP code {$e->getCode()}: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}", 'access');
		}
	}

}
