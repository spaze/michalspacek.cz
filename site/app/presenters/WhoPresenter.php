<?php
/**
 * Who presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class WhoPresenter extends BasePresenter
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
		$this->template->pageTitle  = 'Kdo?';
		$this->template->pageHeader = 'Kdo je Michal Špaček?';
	}


}