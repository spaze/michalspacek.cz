<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks;

use MichalSpacekCz\Application\UiControl;

class TalksList extends UiControl
{

	/**
	 * @param list<Talk> $talks
	 */
	public function render(array $talks): void
	{
		$this->template->talks = $talks;
		$this->template->render(__DIR__ . '/talksList.latte');
	}

}
