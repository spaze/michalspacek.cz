<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use MichalSpacekCz\Redirections;
use Nette\Application\Request;
use Nette\Http\IResponse;
use Tracy\ILogger;

abstract class BaseErrorPresenter extends BasePresenter
{

	/** @var Redirections */
	protected $redirections;

	/** @var ILogger */
	private $logger;

	/** @var integer[] */
	protected $statuses = [
		IResponse::S400_BAD_REQUEST,
		IResponse::S403_FORBIDDEN,
		IResponse::S404_NOT_FOUND,
		IResponse::S405_METHOD_NOT_ALLOWED,
		IResponse::S410_GONE,
	];


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
		if (!$this->getRequest()->isMethod(Request::FORWARD)) {
			$this->error();
		}

		$destination = $this->redirections->getDestination($this->getHttpRequest()->getUrl());
		if ($destination) {
			$this->redirectUrl($destination, IResponse::S301_MOVED_PERMANENTLY);
		}

		$e = $this->getRequest()->getParameter('exception');
		$this->logger->log("HTTP code {$e->getCode()}: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}", 'access');
	}

}
