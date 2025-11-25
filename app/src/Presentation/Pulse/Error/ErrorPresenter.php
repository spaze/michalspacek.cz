<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Pulse\Error;

use MichalSpacekCz\Presentation\Www\BaseErrorPresenter;
use MichalSpacekCz\Pulse\Error\PulseError;
use Override;

final class ErrorPresenter extends BaseErrorPresenter
{

	#[Override]
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
