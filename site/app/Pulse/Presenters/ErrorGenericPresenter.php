<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Presenters;

use MichalSpacekCz\Application\Error;
use Nette\Application\IPresenter;
use Nette\Application\Request;
use Nette\Application\Response;
use Override;

class ErrorGenericPresenter implements IPresenter
{

	public function __construct(
		private readonly Error $error,
	) {
	}


	#[Override]
	public function run(Request $request): Response
	{
		return $this->error->response($request);
	}

}
