<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use MichalSpacekCz\Application\AppRequest;
use MichalSpacekCz\Application\Exceptions\NoOriginalRequestException;
use MichalSpacekCz\Application\LocaleLinkGeneratorInterface;
use MichalSpacekCz\EasterEgg\FourOhFourButFound;
use Nette\Application\BadRequestException;
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
		private readonly FourOhFourButFound $fourOhFourButFound,
		private readonly AppRequest $appRequest,
	) {
		parent::__construct();
	}


	protected function addLocaleLinks(): void
	{
		try {
			$this->template->localeLinks = $this->localeLinkGenerator->links($this->getLocaleLinkAction(), $this->getLocaleLinkParams());
		} catch (NoOriginalRequestException) {
			$this->template->localeLinks = $this->getLocaleLinkDefault();
		}
	}


	public function actionDefault(BadRequestException $exception): void
	{
		$this->fourOhFourButFound->sendItMaybe($this);
		$code = (in_array($exception->getCode(), $this->statuses) ? $exception->getCode() : IResponse::S400_BadRequest);
		$this->template->errorCode = $code;
		$this->template->pageTitle = $this->translator->translate("messages.title.error{$code}");
		$this->template->note = $this->translator->translate("messages.error.{$code}");
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
	 * @throws NoOriginalRequestException
	 */
	protected function getLocaleLinkAction(): string
	{
		$originalRequest = $this->appRequest->getOriginalRequest($this->getRequest());
		return $originalRequest->getPresenterName() . ':' . $originalRequest->getParameter(self::ActionKey);
	}


	/**
	 * Get original parameters for locale links.
	 *
	 * @return array<string, array<string, string|null>>
	 * @throws NoOriginalRequestException
	 */
	protected function getLocaleLinkParams(): array
	{
		$originalRequest = $this->appRequest->getOriginalRequest($this->getRequest());
		return $this->localeLinkGenerator->defaultParams($originalRequest->getParameters());
	}

}
