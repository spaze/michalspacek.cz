<?php
/**
 * Contact presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class ContactPresenter extends BasePresenter
{


	public function __construct(\Nette\Localization\ITranslator $translator)
	{
		parent::__construct($translator);
	}


	public function renderDefault()
	{
		$this->template->pageTitle  = $this->translator->translate('Kontakt');
	}



}