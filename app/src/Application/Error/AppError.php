<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Error;

use MichalSpacekCz\Application\AppRequest;
use Nette\Application\BadRequestException;
use Nette\Application\Helpers;
use Nette\Application\Request;
use Nette\Application\Responses\ForwardResponse;
use Nette\Application\Responses\TextResponse;
use Tracy\ILogger;

final readonly class AppError
{

	public function __construct(
		private ILogger $logger,
		private AppRequest $appRequest,
	) {
	}


	public function response(Request $request): ForwardResponse|TextResponse
	{
		$e = $this->appRequest->getException($request);

		if ($e instanceof BadRequestException) {
			[$module, , $sep] = Helpers::splitName($request->getPresenterName());
			return new ForwardResponse($request->setPresenterName($module . $sep . 'Error'));
		}

		$this->logger->log($e, ILogger::EXCEPTION);
		return new TextResponse(file_get_contents(__DIR__ . '/appError.html'));
	}

}
