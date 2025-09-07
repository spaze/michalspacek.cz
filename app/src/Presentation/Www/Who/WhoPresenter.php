<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Www\Who;

use Contributte\Translation\Translator;
use MichalSpacekCz\Presentation\Www\BasePresenter;
use MichalSpacekCz\Talks\Talks;

final class WhoPresenter extends BasePresenter
{

	public function __construct(
		private readonly Talks $talks,
		private readonly Translator $translator,
	) {
		parent::__construct();
	}


	public function renderDefault(): void
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.who');
		$this->template->pageHeader = $this->translator->translate('messages.header.who');
		$this->template->talksApproxCount = $this->talks->getApproxCount();
	}

}
