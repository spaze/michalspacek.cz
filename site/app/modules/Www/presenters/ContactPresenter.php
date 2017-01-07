<?php
namespace App\WwwModule\Presenters;

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
		$this->template->keyFile = $keyFile = 'key.asc';
		$this->template->key = file_get_contents($keyFile);
	}


}
