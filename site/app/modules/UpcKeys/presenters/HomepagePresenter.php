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
	}


	/**
	 * Default action handler.
	 *
	 * Doesn't use flash messages because I want the errors to be gone after post-redirect-get.
	 *
	 * @param string|NULL $ssid
	 */
	public function actionDefault($ssid = null)
	{
		$this->ssid = $ssid;
		if ($this->ssid !== null) {
			if ($this->ssid !== strtoupper($this->ssid)) {
				$this->redirect('this', strtoupper($this->ssid));
			}
			if ($this->upcKeys->isValidSsid($this->ssid)) {
				$keys = $this->upcKeys->getKeys($this->ssid);
				if (!$keys) {
					$this->template->error = 'Oops, something went wrong, please try again in a moment';
				} else {
					$this->template->keys = $this->enrichKeys($keys);
				}
				$this->template->ssid = $this->ssid;
				$types = $this->types;
				unset($types[\MichalSpacekCz\UpcKeys::SSID_TYPE_UNKNOWN]);
				$this->template->filterTypes = $types;
			} else {
				$this->template->error = 'Wi-Fi network name is not "UPC" and 7 numbers, the password cannot be recovered by this tool';
			}
		}
		$this->template->modelsWithPrefixes = $this->upcKeys->getModelsWithPrefixes();
		$this->template->prefixes = $this->upcKeys->getPrefixes();
		$this->template->placeholder = $this->upcKeys->getSsidPlaceholder();
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
