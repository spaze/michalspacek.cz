<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\SessionGarbageCollector;

use MichalSpacekCz\Application\UiControl;

final class SessionGarbageCollectorStatusMessage extends UiControl
{

	public function __construct(
		private readonly SessionGarbageCollectorStatusFactory $sessionGarbageCollectorStatusFactory,
	) {
	}


	public function render(): void
	{
		$this->template->status = $this->sessionGarbageCollectorStatusFactory->createStatus();
		$this->template->render(__DIR__ . '/sessionGarbageCollectorStatusMessage.latte');
	}

}
