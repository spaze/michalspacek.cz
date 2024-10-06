<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Css;

use MichalSpacekCz\Application\UiControl;

class CriticalCss extends UiControl
{

	public function render(): void
	{
		$this->template->render(__DIR__ . '/criticalCss.latte');
	}

}
