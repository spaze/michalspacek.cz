<?php
declare(strict_types = 1);

namespace MichalSpacekCz\EasterEgg;

use MichalSpacekCz\Http\Robots\Robots;
use MichalSpacekCz\Http\Robots\RobotsRule;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\IRequest;

readonly class FourOhFourButFound
{

	private const array TEMPLATES = [
		'/etc/passwd' => __DIR__ . '/templates/etcPasswd.html',
	];


	public function __construct(
		private IRequest $httpRequest,
		private readonly Robots $robots,
	) {
	}


	public function sendItMaybe(Presenter $presenter): void
	{
		$url = $this->httpRequest->getUrl();
		foreach (self::TEMPLATES as $request => $template) {
			if (str_contains($url->getPath(), $request)) {
				$this->sendIt($presenter, $template);
			} else {
				$query = $url->getQuery();
				if ($query && str_contains(urldecode($query), $request)) {
					$this->sendIt($presenter, $template);
				}
			}
		}
	}


	private function sendIt(Presenter $presenter, string $template): never
	{
		$this->robots->setHeader([RobotsRule::NoIndex, RobotsRule::NoFollow]);
		$presenter->sendResponse(new TextResponse(file_get_contents($template)));
	}

}
