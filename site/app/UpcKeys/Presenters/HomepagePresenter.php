<?php
declare(strict_types = 1);

namespace MichalSpacekCz\UpcKeys\Presenters;

use MichalSpacekCz\Form\UiForm;
use MichalSpacekCz\Form\UpcKeysSsidFormFactory;
use MichalSpacekCz\UpcKeys\UpcKeys;
use MichalSpacekCz\UpcKeys\WiFiBand;
use MichalSpacekCz\Www\Presenters\BasePresenter;
use Nette\Application\BadRequestException;
use Nette\Http\IResponse;

class HomepagePresenter extends BasePresenter
{

	private ?string $ssid = null;


	public function __construct(
		private readonly UpcKeys $upcKeys,
		private readonly IResponse $httpResponse,
		private readonly UpcKeysSsidFormFactory $upcKeysSsidFormFactory,
	) {
		parent::__construct();
	}


	/**
	 * Default action handler.
	 *
	 * Doesn't use flash messages because I want the errors to be gone after post-redirect-get.
	 *
	 * @param string|null $ssid
	 * @param string $format
	 */
	public function actionDefault(?string $ssid = null, string $format = 'html'): void
	{
		$this->ssid = $ssid;
		if ($this->ssid !== null) {
			if ($this->upcKeys->isValidSsid($this->ssid)) {
				if ($this->ssid !== strtoupper($this->ssid)) {
					$this->redirect('this', strtoupper($this->ssid));
				}
				$keys = $this->upcKeys->getKeys($this->ssid);
				if (!$keys) {
					$error = 'Oops, something went wrong, please try again in a moment';
				}
			} else {
				$error = 'Wi-Fi network name is not "UPC" and 7 numbers, the password cannot be recovered by this tool';
				$this->httpResponse->setCode(IResponse::S404_NotFound);
			}
		}

		$this->template->ssid = $this->ssid;
		$this->template->error = $error ?? null;
		$this->template->keys = isset($keys) && !isset($error) ? $keys : null;
		switch ($format) {
			case 'text':
				$this->httpResponse->setContentType('text/plain');
				$this->sendResponse($this->upcKeys->getTextResponse($this->ssid, $error ?? null, $keys ?? []));
				// no break, Presenter::sendResponse() is in earlyTerminatingMethodCalls defined in the phpstan-nette extension config
			case 'html':
				$this->template->filterTypes = WiFiBand::getKnown();
				$this->template->modelsWithPrefixes = $this->upcKeys->getModelsWithPrefixes();
				$this->template->prefixes = $this->upcKeys->getPrefixes();
				$this->template->placeholder = $this->upcKeys->getSsidPlaceholder();
				break;
			case 'json':
				$this->sendJson(array_filter([
					'ssid' => $this->ssid,
					'error' => $error ?? null,
					'keys' => $keys ?? null,
				]));
				// no break, Presenter::sendJson() is in earlyTerminatingMethodCalls defined in the phpstan-nette extension config
			default:
				throw new BadRequestException('Unknown format');
		}
	}


	protected function createComponentSsid(): UiForm
	{
		return $this->upcKeysSsidFormFactory->create(
			function (string $ssid): never {
				$this->redirect('this', $ssid);
			},
			$this->ssid,
		);
	}

}
