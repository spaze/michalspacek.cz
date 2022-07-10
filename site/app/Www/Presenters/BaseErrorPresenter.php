<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use MichalSpacekCz\Http\Redirections;
use MichalSpacekCz\ShouldNotHappenException;
use Nette\Application\Request;
use Nette\Http\IResponse;
use Tracy\ILogger;

abstract class BaseErrorPresenter extends BasePresenter
{

	private Redirections $redirections;
	private ILogger $logger;
	protected bool $logAccess = true;


	/**
	 * @internal
	 * @param Redirections $redirections
	 */
	public function injectRedirections(Redirections $redirections): void
	{
		$this->redirections = $redirections;
	}


	/**
	 * @internal
	 * @param ILogger $logger
	 */
	public function injectLogger(ILogger $logger): void
	{
		$this->logger = $logger;
	}


	public function startup(): void
	{
		parent::startup();
		$request = $this->getRequest();
		if (!$request) {
			throw new ShouldNotHappenException('Request should be set before this method is called in UI\Presenter::run()');
		}
		if (!$request->isMethod(Request::FORWARD)) {
			$this->error();
		}

		$destination = $this->redirections->getDestination($this->getHttpRequest()->getUrl());
		if ($destination) {
			$this->redirectUrl($destination, IResponse::S301_MOVED_PERMANENTLY);
		}

		if ($this->logAccess) {
			$e = $request->getParameter('exception');
			$this->logger->log("HTTP code {$e->getCode()}: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}", 'access');
		}
	}

}
