<?php
/**
 * Contact presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class ContactPresenter extends BasePresenter
{


	/**
	 * @param \Nette\Localization\ITranslator $translator
	 */
	public function __construct(\Nette\Localization\ITranslator $translator)
	{
		parent::__construct($translator);
	}


	public function renderDefault()
	{
		$this->template->pageTitle  = $this->translator->translate('messages.title.contact');
	}



}