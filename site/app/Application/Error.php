<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\ShouldNotHappenException;
use Nette\Application\BadRequestException;
use Nette\Application\Helpers;
use Nette\Application\Request;
use Nette\Application\Response;
use Nette\Application\Responses\CallbackResponse;
use Nette\Application\Responses\ForwardResponse;
use Tracy\ILogger;

readonly class Error
{

	public function __construct(
		private ILogger $logger,
		private AppRequest $appRequest,
	) {
	}


	public function response(Request $request): Response
	{
		$e = $this->appRequest->getException($request);

		if ($e instanceof BadRequestException) {
			[$module, , $sep] = Helpers::splitName($request->getPresenterName());
			if (!is_string($module)) {
				throw new ShouldNotHappenException(sprintf('Module should be a string, %s provided', get_debug_type($module)));
			}
			if (!is_string($sep)) {
				throw new ShouldNotHappenException(sprintf('Separator should be a string, %s provided', get_debug_type($sep)));
			}
			return new ForwardResponse($request->setPresenterName($module . $sep . 'Error'));
		}

		$this->logger->log($e, ILogger::EXCEPTION);
		return new CallbackResponse(function (): void {
			require __DIR__ . '/templates/error.phtml';
		});
	}

}
