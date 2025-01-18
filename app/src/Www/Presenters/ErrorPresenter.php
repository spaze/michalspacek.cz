<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use Contributte\Translation\Translator;
use MichalSpacekCz\Application\AppRequest;
use MichalSpacekCz\Application\Exceptions\NoOriginalRequestException;
use MichalSpacekCz\Application\Locale\LocaleLink;
use MichalSpacekCz\Application\Locale\LocaleLinkGenerator;
use MichalSpacekCz\ShouldNotHappenException;
use Nette\Application\BadRequestException;
use Nette\Application\UI\InvalidLinkException;
use Nette\Http\IResponse;
use Nette\Http\Url;
use Override;

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
		private readonly LocaleLinkGenerator $localeLinkGenerator,
		private readonly AppRequest $appRequest,
		private readonly Translator $translator,
	) {
		parent::__construct();
	}


	/**
	 * @throws InvalidLinkException
	 */
	#[Override]
	protected function getLocaleLinksGeneratorDestination(): string
	{
		try {
			return $this->getLocaleLinkAction();
		} catch (NoOriginalRequestException $e) {
			throw new InvalidLinkException(previous: $e);
		}
	}


	/**
	 * @throws InvalidLinkException
	 */
	#[Override]
	protected function getLocaleLinksGeneratorParams(): array
	{
		try {
			return $this->getLocaleLinkParams();
		} catch (NoOriginalRequestException $e) {
			throw new InvalidLinkException(previous: $e);
		}
	}


	public function actionDefault(BadRequestException $exception): void
	{
		$code = (in_array($exception->getCode(), $this->statuses) ? $exception->getCode() : IResponse::S400_BadRequest);
		$this->template->errorCode = $code;
		$this->template->pageTitle = $this->translator->translate("messages.title.error{$code}");
		$this->template->note = $this->translator->translate("messages.error.{$code}");
	}


	/**
	 * The default locale links.
	 *
	 * @return array<string, LocaleLink> of locale => URL
	 */
	#[Override]
	protected function getLocaleLinkDefault(): array
	{
		$links = [];
		// Change the request host to the localized "homepage" host
		foreach ($this->localeLinkGenerator->links('Www:Homepage:') as $locale => $link) {
			$links[$locale] = $link->withUrl($this->getHttpRequest()->getUrl()->withHost((new Url($link->getUrl()))->getHost())->getAbsoluteUrl());
		}
		return $links;
	}


	/**
	 * Get original module:presenter:action for locale links.
	 *
	 * @throws NoOriginalRequestException
	 */
	#[Override]
	protected function getLocaleLinkAction(): string
	{
		$requestParam = $this->appRequest->getOriginalRequest($this->getRequest());
		$action = $requestParam->getParameter(self::ActionKey);
		if (!is_string($action)) {
			throw new ShouldNotHappenException(sprintf('Action should be a string, %s provided', get_debug_type($action)));
		}
		return $requestParam->getPresenterName() . ':' . $action;
	}


	/**
	 * Get original parameters for locale links.
	 *
	 * @return array<string, array<array-key, mixed>>
	 * @throws NoOriginalRequestException
	 */
	#[Override]
	protected function getLocaleLinkParams(): array
	{
		$requestParam = $this->appRequest->getOriginalRequest($this->getRequest());
		return $this->localeLinkGenerator->defaultParams($requestParam->getParameters());
	}

}
