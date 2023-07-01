<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use MichalSpacekCz\Application\AppRequest;
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
	 */
	protected function getLocaleLinkAction(): string
	{
		$requestParam = $this->appRequest->getOriginalRequest($this->getRequest());
		return $requestParam->getPresenterName() . ':' . $requestParam->getParameter(self::ActionKey);
	}


	/**
	 * Get original parameters for locale links.
	 *
	 * @return array<string, array<string, string|null>>
	 */
	protected function getLocaleLinkParams(): array
	{
		$requestParam = $this->appRequest->getOriginalRequest($this->getRequest());
		return $this->localeLinkGenerator->defaultParams($requestParam->getParameters());
	}

}
