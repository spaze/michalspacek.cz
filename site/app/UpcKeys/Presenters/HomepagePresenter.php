<?php
declare(strict_types = 1);

namespace MichalSpacekCz\UpcKeys\Presenters;

use MichalSpacekCz\Form\UpcKeysSsidFormFactory;
use MichalSpacekCz\UpcKeys\UpcKeys;
use MichalSpacekCz\UpcKeys\WiFiBand;
use MichalSpacekCz\UpcKeys\WiFiKey;
use MichalSpacekCz\Www\Presenters\BasePresenter;
use Nette\Application\BadRequestException;
use Nette\Forms\Form;
use Nette\Http\IResponse;

class HomepagePresenter extends BasePresenter
{

	private ?string $ssid;


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
		$keys = $this->loadKeys();
		switch ($format) {
			case 'text':
				foreach ($keys as $key => $value) {
					$this->template->$key = $value;
				}
				$this->setView('text');
				break;
			case 'html':
				foreach ($keys as $key => $value) {
					$this->template->$key = $value;
				}
				$this->template->filterTypes = array_map(function (WiFiBand $band): string {
					return $band->getLabel();
				}, WiFiBand::getKnown());
				$this->template->modelsWithPrefixes = $this->upcKeys->getModelsWithPrefixes();
				$this->template->prefixes = $this->upcKeys->getPrefixes();
				$this->template->placeholder = $this->upcKeys->getSsidPlaceholder();
				break;
			case 'json':
				$this->sendJson($keys);
				// no break, Presenter::sendJson() is in earlyTerminatingMethodCalls defined in the phpstan-nette extension config
			default:
				throw new BadRequestException('Unknown format');
		}
	}


	/**
	 * Check if SSID is valid and load keys
	 *
	 * @return array{}|array{ssid: string, error: string}|array{ssid: string, keys: non-empty-array<int, WiFiKey>}
	 */
	protected function loadKeys(): array
	{
		$result = [];
		if ($this->ssid !== null) {
			$result['ssid'] = $this->ssid;
			if ($this->upcKeys->isValidSsid($this->ssid)) {
				if ($this->ssid !== strtoupper($this->ssid)) {
					$this->redirect('this', strtoupper($this->ssid));
				}
				$keys = $this->upcKeys->getKeys($this->ssid);
				if (!$keys) {
					$result['error'] = 'Oops, something went wrong, please try again in a moment';
				} else {
					$result['keys'] = $keys;
				}
			} else {
				$result['error'] = 'Wi-Fi network name is not "UPC" and 7 numbers, the password cannot be recovered by this tool';
				$this->httpResponse->setCode(IResponse::S404_NotFound);
			}
		}
		return $result;
	}


	protected function createComponentSsid(): Form
	{
		return $this->upcKeysSsidFormFactory->create(
			function (string $ssid): never {
				$this->redirect('this', $ssid);
			},
			function (): void {
				$this->template->error = 'Oops, something went wrong, please try again in a moment';
			},
			$this->ssid,
		);
	}

}
