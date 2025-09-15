<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Error;

use MichalSpacekCz\Http\Robots\Robots;
use MichalSpacekCz\Http\Robots\RobotsRule;
use Nette\Application\Responses\TextResponse;

final readonly class PulseError
{

	public function __construct(
		private Robots $robots,
	) {
	}


	/**
	 * @param callable(TextResponse): void $sendResponse
	 */
	public function action(callable $sendResponse): void
	{
		$this->robots->setHeader([RobotsRule::NoIndex, RobotsRule::NoFollow]);
		$sendResponse(new TextResponse(file_get_contents(__DIR__ . '/notFound.html')));
	}

}
