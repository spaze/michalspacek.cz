<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

class ContactPresenter extends BasePresenter
{

	public function renderDefault(): void
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.contact');
	}

}
