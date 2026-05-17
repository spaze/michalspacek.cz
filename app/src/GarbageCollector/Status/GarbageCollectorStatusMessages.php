<?php
declare(strict_types = 1);

namespace MichalSpacekCz\GarbageCollector\Status;

use MichalSpacekCz\Application\UiControl;

final class GarbageCollectorStatusMessages extends UiControl
{

	public function __construct(
		private readonly GarbageCollectorStatusFactory $garbageCollectorStatusFactory,
	) {
	}


	public function render(): void
	{
		$this->template->statuses = array_values(array_filter(
			$this->garbageCollectorStatusFactory->createStatuses(),
			fn(GarbageCollectorStatus $status): bool => !$status->ok,
		));
		$this->template->render(__DIR__ . '/garbageCollectorStatusMessages.latte');
	}

}
