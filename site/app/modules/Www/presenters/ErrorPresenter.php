<?php
declare(strict_types = 1);

namespace App\WwwModule\Presenters;

use \Nette\Http\IResponse;

/**
 * Error presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class ErrorPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Redirections */
	protected $redirections;

	/** @var \MichalSpacekCz\Application\LocaleLinkGenerator */
	protected $localeLinkGenerator;

	/** @var \Tracy\ILogger */
	private $logger;

	/** @var array */
	protected $statuses = [
		IResponse::S400_BAD_REQUEST,
		IResponse::S403_FORBIDDEN,
		IResponse::S404_NOT_FOUND,
		IResponse::S405_METHOD_NOT_ALLOWED,
		IResponse::S410_GONE,
	];


	/**
	 * @param \MichalSpacekCz\Redirections $redirections
	 * @param \MichalSpacekCz\Application\LocaleLinkGenerator $localeLinkGenerator
	 */
	public function __construct(\MichalSpacekCz\Redirections $redirections, \MichalSpacekCz\Application\LocaleLinkGenerator $localeLinkGenerator, \Tracy\ILogger $logger)
	{
		$this->redirections = $redirections;
		$this->localeLinkGenerator = $localeLinkGenerator;
		$this->logger = $logger;
		parent::__construct();
	}


	public function startup(): void
	{
		parent::startup();
		if (!$this->getRequest()->isMethod(\Nette\Application\Request::FORWARD)) {
			$this->error();
		}

		$destination = $this->redirections->getDestination($this->getHttpRequest()->getUrl());
		if ($destination) {
			$this->redirectUrl($destination, IResponse::S301_MOVED_PERMANENTLY);
		}

		$e = $this->getRequest()->getParameter('exception');
		$this->logger->log("HTTP code {$e->getCode()}: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}", 'access');
	}


	/**
	 * @param \Nette\Application\BadRequestException $exception
	 */
	public function actionDefault(\Nette\Application\BadRequestException $exception): void
	{
		$code = (in_array($exception->getCode(), $this->statuses) ? $exception->getCode() : IResponse::S400_BAD_REQUEST);
		$this->template->errorCode = $code;
		$this->template->pageTitle = $this->translator->translate("messages.title.error{$code}");
		$this->template->note =  $this->translator->translate("messages.error.{$code}");
	}


	/**
	 * The default locale links.
	 *
	 * @return array|null
	 */
	protected function getLocaleLinkDefault(): ?array
	{
		// Change the request host to the localized "homepage" host
		$links = $this->localeLinkGenerator->links('Www:Homepage:');
		foreach ($links as &$link) {
			$url = $this->getHttpRequest()->getUrl();
			$url->setHost((new \Nette\Http\Url($link))->getHost());
			$link = $url->getAbsoluteUrl();
		}
		return $links;
	}


	/**
	 * Get original module:presenter:action for locale links.
	 *
	 * @return string
	 */
	protected function getLocaleLinkAction(): string
	{
		$request = $this->getRequest()->getParameter('request');
		if (!$request) {
			throw new \Nette\Application\UI\InvalidLinkException('No request');
		}
		return $request->getPresenterName() . ':' . $request->getParameter(self::ACTION_KEY);
	}


	/**
	 * Get original parameters for locale links.
	 *
	 * @return array
	 */
	protected function getLocaleLinkParams(): array
	{
		return $this->localeLinkGenerator->defaultParams($this->getRequest()->getParameter('request')->getParameters());
	}

}
