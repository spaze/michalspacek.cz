<?php
namespace App\UpcKeysModule\Presenters;

use \Nette\Application\UI\Form;

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
			$this->template->keys = $this->upcKeys->getKeys($this->ssid);
		}
	}


	protected function createComponentSsid($formName)
	{
		$form = new Form($this, $formName);
		$form->addText('ssid', 'SSID:')
			->setAttribute('placeholder', 'UPC1234567')
			->setDefaultValue($this->ssid)
			->setRequired('Please enter an SSID')
			->addRule(Form::PATTERN, 'SSID has to start with "UPC"', '\s*([Uu][Pp][Cc]).*');
		$form->addSubmit('submit', 'Get keys')
			->setHtmlId('submit')
			->setAttribute('data-alt', 'Wait…');
		$form->onSuccess[] = $this->submittedSsid;

		return $form;
	}


	public function submittedSsid(Form $form)
	{
		$values = $form->getValues();
		$ssid = trim($values->ssid);
		$this->redirect('this', $ssid);
	}


}
