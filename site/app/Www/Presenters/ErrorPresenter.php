<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use MichalSpacekCz\Application\LocaleLinkGeneratorInterface;
use MichalSpacekCz\ShouldNotHappenException;
use Nette\Application\BadRequestException;
use Nette\Application\UI\InvalidLinkException;
use Nette\Http\IResponse;
use Nette\Http\Url;

class ErrorPresenter extends BaseErrorPresenter
{

	/** @var int[] */
	private array $statuses = [
		IResponse::S400_BadRequest,
		IResponse::S403_Forbidden,
		IResponse::S404_NotFound,
		IResponse::S405_MethodNotAllowed,
		IResponse::S410_Gone,
	];


	public function __construct(
		private readonly LocaleLinkGeneratorInterface $localeLinkGenerator,
	) {
		parent::__construct();
	}


	public function actionDefault(BadRequestException $exception): void
	{
		$code = (in_array($exception->getCode(), $this->statuses) ? $exception->getCode() : IResponse::S400_BadRequest);
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
		$request = $this->getRequest();
		if (!$request) {
			throw new ShouldNotHappenException('Request should be set before this method is called in UI\Presenter::run()');
		}
		$requestParam = $request->getParameter('request');
		if (!$requestParam) {
			throw new InvalidLinkException('No request');
		}
		return $requestParam->getPresenterName() . ':' . $requestParam->getParameter(self::ACTION_KEY);
	}


	/**
	 * Get original parameters for locale links.
	 *
	 * @return array<string, array<string, string|null>>
	 * @throws ShouldNotHappenException
	 */
	protected function getLocaleLinkParams(): array
	{
		$request = $this->getRequest();
		if (!$request) {
			throw new ShouldNotHappenException('Request should be set before this method is called in UI\Presenter::run()');
		}
		return $this->localeLinkGenerator->defaultParams($request->getParameter('request')->getParameters());
	}

}
