<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Api\ErrorGeneric;

use MichalSpacekCz\Api\Endpoint\NotAnEndpoint;
use MichalSpacekCz\Application\Error\AppError;
use Nette\Application\IPresenter;
use Nette\Application\Request;
use Nette\Application\Response;
use Override;

#[NotAnEndpoint]
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
