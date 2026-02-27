<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter\TexyPhraseHandler\Shortcuts;

use MichalSpacekCz\Application\WebApplication;
use Nette\Application\UI\InvalidLinkException;
use Override;
use Texy\HandlerInvocation;
use Texy\Link;
use Texy\Modifier;

final readonly class TexyShortcutTalk implements TexyShortcut
{

	private const string PREFIX = 'talk:';


	public function __construct(
		private WebApplication $webApplication,
	) {
	}


	#[Override]
	public function canResolve(string $url): bool
	{
		return str_starts_with($url, self::PREFIX);
	}


	/**
	 * @throws InvalidLinkException
	 */
	#[Override]
	public function resolve(string $url, HandlerInvocation $invocation, string $phrase, string $content, Modifier $modifier, Link $link): null
	{
		// "title":[talk:title#slide]
		$args = explode('#', substr($url, strlen(self::PREFIX)));
		if ($args[0] === '') {
			throw new InvalidLinkException(sprintf('No talk specified in [%s]', self::PREFIX));
		}
		$params = [
			'name' => $args[0],
			'slide' => $args[1] ?? null,
		];
		$link->URL = $this->webApplication->getPresenter()->link('//:Www:Talks:talk', $params);
		return null;
	}

}
