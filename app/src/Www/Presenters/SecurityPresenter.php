<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use Contributte\Translation\Translator;

class SecurityPresenter extends BasePresenter
{

	public function __construct(
		private readonly Translator $translator,
	) {
		parent::__construct();
	}


	public function renderDefault(): void
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.who');
		$this->template->pageHeader = $this->translator->translate('messages.header.who');
	}

}
