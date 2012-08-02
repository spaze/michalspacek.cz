<?php
/**
 * Přednášky presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class PrednaskyPresenter extends BasePresenter
{


	public function renderDefault()
	{
		$this->template->pageTitle = 'Přednášky';
		$this->template->talks     = $this->getService('talks')->getAll();
	}


}
