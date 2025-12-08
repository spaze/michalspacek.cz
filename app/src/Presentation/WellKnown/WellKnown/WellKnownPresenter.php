<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\WellKnown\WellKnown;

use MichalSpacekCz\Presentation\Www\BasePresenter;
use MichalSpacekCz\SecurityTxt\SecurityTxtResponse;

final class WellKnownPresenter extends BasePresenter
{

	public function __construct(
		private readonly SecurityTxtResponse $securityTxtResponse,
	) {
		parent::__construct();
	}


	public function actionSecurityTxt(): void
	{
		$this->sendResponse($this->securityTxtResponse->getResponse());
	}

}
