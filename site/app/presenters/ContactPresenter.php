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


	public function renderDefault()
	{
		$this->template->pageTitle  = $this->translator->translate('messages.title.contact');
	}


}
