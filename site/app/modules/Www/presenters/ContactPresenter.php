<?php
declare(strict_types = 1);

namespace App\WwwModule\Presenters;

class ContactPresenter extends BasePresenter
{

	public function renderDefault(): void
	{
		$this->template->pageTitle  = $this->translator->translate('messages.title.contact');
		$this->template->keyFile = $keyFile = 'key.asc';
		$this->template->key = file_get_contents($keyFile);
	}

}
