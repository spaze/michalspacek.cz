<?php
declare(strict_types = 1);

namespace MichalSpacekCz\EasterEgg\FourOhFourButFound;

use MichalSpacekCz\Application\ServerEnv;
use MichalSpacekCz\Http\Robots\Robots;
use MichalSpacekCz\Http\Robots\RobotsRule;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\IResponse;

final readonly class FourOhFourButFound
{

	private const array TEMPLATES = [
		'?%ad' => __DIR__ . '/phpCve20244577.html',
		'/etc/passwd' => __DIR__ . '/etcPasswd.html',
	];


	public function __construct(
		private Robots $robots,
		private IResponse $httpResponse,
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
				$this->httpResponse->setCode(IResponse::S404_NotFound, 'This is a not found page source trust me bro');
				$this->robots->setHeader([RobotsRule::NoIndex, RobotsRule::NoFollow]);
				$presenter->sendResponse(new TextResponse(file_get_contents($template)));
			}
		}
	}


	/**
	 * @return list<string>
	 */
	public function getRequestSubstrings(): array
	{
		return array_keys(self::TEMPLATES);
	}

}
