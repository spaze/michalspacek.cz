<?php
namespace App\WwwModule\Presenters;

/**
 * Who presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class WhoPresenter extends BasePresenter
{


	public function renderDefault()
	{
		$this->template->pageTitle  = $this->translator->translate('messages.title.who');
		$this->template->pageHeader = $this->translator->translate('messages.header.who');
	}


}
