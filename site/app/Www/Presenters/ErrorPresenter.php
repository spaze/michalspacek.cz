<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use MichalSpacekCz\Application\LocaleLinkGenerator;
use Nette\Application\BadRequestException;
use Nette\Application\UI\InvalidLinkException;
use Nette\Http\IResponse;
use Nette\Http\Url;

class ErrorPresenter extends BaseErrorPresenter
{

	private LocaleLinkGenerator $localeLinkGenerator;

	/** @var int[] */
	private array $statuses = [
		IResponse::S400_BAD_REQUEST,
		IResponse::S403_FORBIDDEN,
		IResponse::S404_NOT_FOUND,
		IResponse::S405_METHOD_NOT_ALLOWED,
		IResponse::S410_GONE,
	];


	public function __construct(LocaleLinkGenerator $localeLinkGenerator)
	{
		$this->localeLinkGenerator = $localeLinkGenerator;
		parent::__construct();
	}


	public function actionDefault(BadRequestException $exception): void
	{
		$code = (in_array($exception->getCode(), $this->statuses) ? $exception->getCode() : IResponse::S400_BAD_REQUEST);
		$this->template->errorCode = $code;
		$this->template->pageTitle = $this->translator->translate("messages.title.error{$code}");
		$this->template->note =  $this->translator->translate("messages.error.{$code}");
	}


	/**
	 * The default locale links.
	 *
	 * @return string[]|null
	 */
	protected function getLocaleLinkDefault(): ?array
	{
		// Change the request host to the localized "homepage" host
		$links = $this->localeLinkGenerator->links('Www:Homepage:');
		foreach ($links as &$link) {
			$link = $this->getHttpRequest()->getUrl()->withHost((new Url($link))->getHost())->getAbsoluteUrl();
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
			throw new InvalidLinkException('No request');
		}
		return $request->getPresenterName() . ':' . $request->getParameter(self::ACTION_KEY);
	}


	/**
	 * Get original parameters for locale links.
	 *
	 * @return array<string, array<string, string>>
	 */
	protected function getLocaleLinkParams(): array
	{
		return $this->localeLinkGenerator->defaultParams($this->getRequest()->getParameter('request')->getParameters());
	}

}
