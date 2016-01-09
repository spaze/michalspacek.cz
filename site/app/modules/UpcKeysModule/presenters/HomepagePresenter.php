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

	/** @var \Nette\Database\Context */
	protected $database;

	/** @var \MichalSpacekCz\UpcKeys */
	protected $upcKeys;


	public function __construct(\Nette\Database\Context $context, \MichalSpacekCz\UpcKeys $upcKeys)
	{
		$this->database = $context;
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
			if ($this->upcKeys->isUpcSsid($this->ssid)) {
				if (!$this->upcKeys->isValidSsid($this->ssid)) {
					$this->template->error = 'Wi-Fi network name is not UPC + 7 numbers, the password might not be listed below';
				}
				$this->template->keys = $this->upcKeys->getKeys($this->ssid);
			} else {
				$this->template->error = 'Wi-Fi network name has to start with "UPC"';
			}
		}
		$this->template->placeholder = $this->upcKeys->getSsidPlaceholder();
	}


	protected function createComponentSsid($formName)
	{
		$form = new \MichalSpacekCz\Form\UpcKeys($this, $formName, $this->ssid, $this->upcKeys);
		$form->onSuccess[] = $this->submittedSsid;
		return $form;
	}


	public function submittedSsid(\MichalSpacekCz\Form\UpcKeys $form)
	{
		$values = $form->getValues();
		$ssid = trim($values->ssid);
		$this->upcKeys->saveKeys($ssid);
		$this->redirect('this', $ssid);
	}


}
