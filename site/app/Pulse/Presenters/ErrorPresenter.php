<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Presenters;

use MichalSpacekCz\Www\Presenters\BaseErrorPresenter;
use Nette\Application\AbortException;
use Nette\Application\Responses\TextResponse;

class ErrorPresenter extends BaseErrorPresenter
{

	/**
	 * @throws AbortException
	 */
	public function actionDefault(): never
	{
		$this->sendResponse(new TextResponse(file_get_contents(__DIR__ . '/templates/Error/notFound.html')));
	}

}
