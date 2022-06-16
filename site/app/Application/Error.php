<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use Nette\Application\BadRequestException;
use Nette\Application\Helpers;
use Nette\Application\Request;
use Nette\Application\Response;
use Nette\Application\Responses\CallbackResponse;
use Nette\Application\Responses\ForwardResponse;
use Tracy\ILogger;

class Error
{

	public function __construct(
		private readonly ILogger $logger,
	) {
	}


	public function response(Request $request): Response
	{
		$e = $request->getParameter('exception');

		if ($e instanceof BadRequestException) {
			list($module, , $sep) = Helpers::splitName($request->getPresenterName());
			return new ForwardResponse($request->setPresenterName($module . $sep . 'Error'));
		}

		$this->logger->log($e, ILogger::EXCEPTION);
		return new CallbackResponse(function (): void {
			require __DIR__ . '/templates/error.phtml';
		});
	}

}
