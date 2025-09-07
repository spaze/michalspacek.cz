<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Www\Contact;

use Contributte\Translation\Translator;
use MichalSpacekCz\Presentation\Www\BasePresenter;

final class ContactPresenter extends BasePresenter
{

	public function __construct(
		private readonly Translator $translator,
	) {
		parent::__construct();
	}


	public function renderDefault(): void
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.contact');
	}

}
