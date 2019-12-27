<?php
declare(strict_types = 1);

namespace App\UpcKeysModule\Presenters;

use App\WwwModule\Presenters\BasePresenter;
use MichalSpacekCz\Form\UpcKeys as UpcKeysForm;
use MichalSpacekCz\UpcKeys;
use Nette\Application\BadRequestException;
use Nette\Forms\Form;
use Nette\Http\IResponse;
use Nette\Utils\ArrayHash;
use RuntimeException;
use stdClass;

class HomepagePresenter extends BasePresenter
{

	/** @var string */
	protected $ssid;

	/** @var UpcKeys */
	protected $upcKeys;

	/** @var IResponse */
	protected $httpResponse;

	/** @var array<integer, string> */
	private $types = [
		UpcKeys::SSID_TYPE_24GHZ => '2.4 GHz',
		UpcKeys::SSID_TYPE_5GHZ => '5 GHz',
		UpcKeys::SSID_TYPE_UNKNOWN => 'unknown',
	];


	public function __construct(UpcKeys $upcKeys, IResponse $httpResponse)
	{
		$this->upcKeys = $upcKeys;
		$this->httpResponse = $httpResponse;
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
				$types = $this->types;
				unset($types[UpcKeys::SSID_TYPE_UNKNOWN]);
				$this->template->filterTypes = $types;
				$this->template->modelsWithPrefixes = $this->upcKeys->getModelsWithPrefixes();
				$this->template->prefixes = $this->upcKeys->getPrefixes();
				$this->template->placeholder = $this->upcKeys->getSsidPlaceholder();
				break;
			/**
			 * @noinspection PhpMissingBreakStatementInspection
			 * `Nette\Application\UI\Presenter::sendJson()` is listed in `earlyTerminatingMethodCalls` in the phpstan-nette extension config
			 * so PHPStan flags it as "Unreachable statement" - removing the `break` fixed it but then PHPStorm started to complain
			 */
			case 'json':
				$this->sendJson($keys);
			default:
				throw new BadRequestException('Unknown format');
		}
	}


	/**
	 * Check if SSID is valid and load keys
	 *
	 * @return array<string, string|stdClass[]>
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
					$result['keys'] = $this->enrichKeys($keys);
				}
			} else {
				$result['error'] = 'Wi-Fi network name is not "UPC" and 7 numbers, the password cannot be recovered by this tool';
				$this->httpResponse->setCode(IResponse::S404_NOT_FOUND);
			}
		}
		return $result;
	}


	/**
	 * Add information to keys.
	 *
	 * @param stdClass[] $keys
	 * @return stdClass[]
	 */
	protected function enrichKeys(array $keys): array
	{
		$prefixes = $this->upcKeys->getPrefixes();
		foreach ($keys as $key) {
			if (!isset($this->types[$key->type])) {
				throw new RuntimeException('Unknown network type ' . $key->type);
			}
			$key->typeId = $key->type;
			$key->type = $this->types[$key->typeId];

			$matches = array();
			preg_match('/^[a-z]+/i', $key->serial, $matches);
			$prefix = current($matches);
			if (!in_array($prefix, $prefixes)) {
				throw new RuntimeException('Unknown prefix for serial ' . $key->serial);
			}
			$key->serialPrefix = $prefix;
		}
		return $keys;
	}


	protected function createComponentSsid(string $formName): UpcKeysForm
	{
		$form = new UpcKeysForm($this, $formName, $this->ssid, $this->upcKeys);
		$form->onSuccess[] = [$this, 'submittedSsid'];
		return $form;
	}


	/**
	 * @param Form $form
	 * @param ArrayHash<integer|string> $values
	 */
	public function submittedSsid(Form $form, ArrayHash $values): void
	{
		$ssid = strtoupper(trim($values->ssid));
		if (!$this->upcKeys->saveKeys($ssid)) {
			$this->template->error = 'Oops, something went wrong, please try again in a moment';
		} else {
			$this->redirect('this', $ssid);
		}
	}

}
