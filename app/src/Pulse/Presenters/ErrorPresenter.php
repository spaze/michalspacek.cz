<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Presenters;

use MichalSpacekCz\Http\Robots\Robots;
use MichalSpacekCz\Http\Robots\RobotsRule;
use MichalSpacekCz\Www\Presenters\BaseErrorPresenter;
use Nette\Application\Responses\TextResponse;

final class ErrorPresenter extends BaseErrorPresenter
{

	protected bool $logAccess = false;


	public function __construct(
		private readonly Robots $robots,
	) {
		parent::__construct();
	}


	public function actionDefault(): never
	{
		$this->robots->setHeader([RobotsRule::NoIndex, RobotsRule::NoFollow]);
		$this->sendResponse(new TextResponse(file_get_contents(__DIR__ . '/templates/Error/notFound.html')));
	}

}
