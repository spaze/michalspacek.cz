<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Pulse\ErrorGeneric;

use MichalSpacekCz\Application\Error\AppError;
use Nette\Application\IPresenter;
use Nette\Application\Request;
use Nette\Application\Response;
use Override;

final readonly class ErrorGenericPresenter implements IPresenter
{

	public function __construct(
		private AppError $error,
	) {
	}


	#[Override]
	public function run(Request $request): Response
	{
		return $this->error->response($request);
	}

}
