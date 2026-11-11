<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Pulse\Error;

use MichalSpacekCz\Presentation\Www\BaseErrorPresenter;
use MichalSpacekCz\Pulse\Error\PulseError;
use MichalSpacekCz\Sitemap\NotInSitemap;
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


	#[NotInSitemap('the error page, shown in place of the page that failed')]
	public function actionDefault(): void
	{
		$this->pulseError->action($this->sendResponse(...));
	}

}
