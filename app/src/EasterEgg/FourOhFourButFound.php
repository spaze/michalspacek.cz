<?php
declare(strict_types = 1);

namespace MichalSpacekCz\EasterEgg;

use MichalSpacekCz\Application\ServerEnv;
use MichalSpacekCz\Http\Robots\Robots;
use MichalSpacekCz\Http\Robots\RobotsRule;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Presenter;

readonly class FourOhFourButFound
{

	private const array TEMPLATES = [
		'?%ad' => __DIR__ . '/templates/phpCve20244577.html',
		'/etc/passwd' => __DIR__ . '/templates/etcPasswd.html',
	];


	public function __construct(
		private Robots $robots,
	) {
	}


	public function sendItMaybe(Presenter $presenter): void
	{
		$url = ServerEnv::tryGetString('REQUEST_URI');
		if ($url === null) {
			return;
		}
		foreach (self::TEMPLATES as $request => $template) {
			if (str_contains($url, $request) || str_contains(urldecode($url), $request)) {
				$this->robots->setHeader([RobotsRule::NoIndex, RobotsRule::NoFollow]);
				$presenter->sendResponse(new TextResponse(file_get_contents($template)));
			}
		}
	}

}
