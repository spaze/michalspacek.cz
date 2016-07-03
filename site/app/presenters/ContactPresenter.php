<?php
namespace App\Presenters;

/**
 * Contact presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class ContactPresenter extends BasePresenter
{

	/** @var \Spaze\SubresourceIntegrity\Config */
	private $sriConfig;


	/**
	 * @param \Spaze\SubresourceIntegrity\Config $sriConfig
	 */
	public function __construct(\Spaze\SubresourceIntegrity\Config $sriConfig)
	{
		$this->sriConfig = $sriConfig;
	}


	public function renderDefault()
	{
		$this->template->pageTitle  = $this->translator->translate('messages.title.contact');
		$this->template->keyFile = $keyFile = 'key.asc';
		$this->template->key = file_get_contents($keyFile);
		$this->template->encryptionLib = $this->sriConfig->getUrl('openpgp');
		$this->template->encryptionIntegrity = $this->sriConfig->getHash('openpgp');
	}


}
