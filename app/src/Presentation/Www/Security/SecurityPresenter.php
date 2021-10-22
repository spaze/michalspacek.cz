<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Www\Security;

use Contributte\Translation\Translator;
use MichalSpacekCz\Presentation\Www\BasePresenter;

final class SecurityPresenter extends BasePresenter
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
