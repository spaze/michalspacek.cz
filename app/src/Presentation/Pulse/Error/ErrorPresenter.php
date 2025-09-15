<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Pulse\Error;

use MichalSpacekCz\Presentation\Www\BaseErrorPresenter;
use MichalSpacekCz\Pulse\Error\PulseError;

final class ErrorPresenter extends BaseErrorPresenter
{

	protected bool $logAccess = false;


	public function __construct(
		private readonly PulseError $pulseError,
	) {
		parent::__construct();
	}


	public function actionDefault(): void
	{
		$this->pulseError->action($this->sendResponse(...));
	}

}
