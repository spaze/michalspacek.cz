<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use Nette\Application\BadRequestException;
use Nette\Application\Helpers;
use Nette\Application\IResponse;
use Nette\Application\Request;
use Nette\Application\Responses\CallbackResponse;
use Nette\Application\Responses\ForwardResponse;
use Tracy\ILogger;

class Error
{

	/** @var ILogger */
	private $logger;


	public function __construct(ILogger $logger)
	{
		$this->logger = $logger;
	}


	public function response(Request $request): IResponse
	{
		$e = $request->getParameter('exception');

		if ($e instanceof BadRequestException) {
			list($module, , $sep) = Helpers::splitName($request->getPresenterName());
			return new ForwardResponse($request->setPresenterName($module . $sep . 'Error'));
		}

		$this->logger->log($e, ILogger::EXCEPTION);
		return new CallbackResponse(function () {
			require __DIR__ . '/templates/error.phtml';
		});
	}

}
