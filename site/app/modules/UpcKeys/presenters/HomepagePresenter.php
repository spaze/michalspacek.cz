<?php
namespace App\UpcKeysModule\Presenters;

/**
 * Homepage presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class HomepagePresenter extends \App\Presenters\BasePresenter
{

	/** @var string */
	protected $ssid;

	/** @var \MichalSpacekCz\UpcKeys */
	protected $upcKeys;

	/** @var array (type id => type) */
	private $types = array(
		\MichalSpacekCz\UpcKeys::SSID_TYPE_24GHZ => '2.4 GHz',
		\MichalSpacekCz\UpcKeys::SSID_TYPE_5GHZ => '5 GHz',
		\MichalSpacekCz\UpcKeys::SSID_TYPE_UNKNOWN => 'unknown',
	);


	public function __construct(\MichalSpacekCz\UpcKeys $upcKeys)
	{
		$this->upcKeys = $upcKeys;
		parent::__construct();
	}


	/**
	 * Default action handler.
	 *
	 * Doesn't use flash messages because I want the errors to be gone after post-redirect-get.
	 *
	 * @param string|NULL $ssid
	 * @param string $format
	 */
	public function actionDefault($ssid = null, $format = 'html')
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
			case 'json':
				$this->sendJson($keys);
				break;
			case 'html':
				foreach ($keys as $key => $value) {
					$this->template->$key = $value;
				}
				$types = $this->types;
				unset($types[\MichalSpacekCz\UpcKeys::SSID_TYPE_UNKNOWN]);
				$this->template->filterTypes = $types;
				$this->template->modelsWithPrefixes = $this->upcKeys->getModelsWithPrefixes();
				$this->template->prefixes = $this->upcKeys->getPrefixes();
				$this->template->placeholder = $this->upcKeys->getSsidPlaceholder();
				break;
			default:
				throw new \Nette\Application\BadRequestException('Unknown format', Response::S404_NOT_FOUND);
		}
	}


	/**
	 * Check if SSID is valid and load keys
	 *
	 * @return array
	 */
	protected function loadKeys()
	{
		$result = [];
		if ($this->ssid !== null) {
			if ($this->ssid !== strtoupper($this->ssid)) {
				$this->redirect('this', strtoupper($this->ssid));
			}
			$result['ssid'] = $this->ssid;
			if ($this->upcKeys->isValidSsid($this->ssid)) {
				$keys = $this->upcKeys->getKeys($this->ssid);
				if (!$keys) {
					$result['error'] = 'Oops, something went wrong, please try again in a moment';
				} else {
					$result['keys'] = $this->enrichKeys($keys);
				}
			} else {
				$result['error'] = 'Wi-Fi network name is not "UPC" and 7 numbers, the password cannot be recovered by this tool';
			}
		}
		return $result;
	}


	/**
	 * Add information to keys.
	 *
	 * @param array (type id => type)
	 * @return array (type id => type)
	 */
	protected function enrichKeys(array $keys)
	{
		$prefixes = $this->upcKeys->getPrefixes();
		foreach ($keys as $key) {
			if (!isset($this->types[$key->type])) {
				throw new \RuntimeException('Unknown network type ' . $key->type);
			}
			$key->typeId = $key->type;
			$key->type = $this->types[$key->typeId];

			$matches = array();
			preg_match('/^[a-z]+/i', $key->serial, $matches);
			$prefix = current($matches);
			if (!in_array($prefix, $prefixes)) {
				throw new \RuntimeException('Unknown prefix for serial ' . $key->serial);
			}
			$key->serialPrefix = $prefix;
		}
		return $keys;
	}


	protected function createComponentSsid($formName)
	{
		$form = new \MichalSpacekCz\Form\UpcKeys($this, $formName, $this->ssid, $this->upcKeys);
		$form->onSuccess[] = [$this, 'submittedSsid'];
		return $form;
	}


	public function submittedSsid(\MichalSpacekCz\Form\UpcKeys $form, $values)
	{
		$ssid = strtoupper(trim($values->ssid));
		if (!$this->upcKeys->saveKeys($ssid)) {
			$this->template->error = 'Oops, something went wrong, please try again in a moment';
		} else {
			$this->redirect('this', $ssid);
		}
	}


}
