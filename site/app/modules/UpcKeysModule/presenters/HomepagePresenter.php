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


	public function actionDefault($ssid = null)
	{
		$this->ssid = $ssid;
		if ($this->ssid !== null) {
			if ($this->upcKeys->isSsidValid($this->ssid)) {
				$this->template->keys = $this->upcKeys->getKeys($this->ssid);
			} else {
				// No flash message because I want this to be gone after post-redirect-get
				$this->template->error = 'SSID has to start with "UPC"';
			}
		}
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
		$this->redirect('this', $ssid);
	}


}
