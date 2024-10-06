<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks;

use Contributte\Translation\Translator;
use MichalSpacekCz\Application\UiControl;

class TalksList extends UiControl
{

	public function __construct(
		private readonly Translator $translator,
	) {
	}


	/**
	 * @param list<Talk> $talks
	 */
	public function render(array $talks): void
	{
		$this->template->defaultLocale = $this->translator->getDefaultLocale();
		$this->template->talks = $talks;
		$this->template->render(__DIR__ . '/talksList.latte');
	}

}
