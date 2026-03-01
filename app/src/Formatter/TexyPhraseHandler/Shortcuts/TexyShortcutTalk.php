<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter\TexyPhraseHandler\Shortcuts;

use MichalSpacekCz\Application\WebApplication;
use MichalSpacekCz\Talks\Slides\TalkSlides;
use MichalSpacekCz\Talks\Talks;
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
		private Talks $talks,
		private TalkSlides $talkSlides,
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
		$talkId = $this->talks->getId($args[0]);
		if ($talkId === null) {
			throw new InvalidLinkException("Talk specified in [{$url}] doesn't exist");
		}
		$params = ['name' => $args[0]];
		$slide = $args[1] ?? null;
		if ($slide !== null) {
			if (!$this->talkSlides->hasSlideAlias($talkId, $slide)) {
				throw new InvalidLinkException("The slide linked in [{$url}] doesn't exist, only the talk does");
			}
			$params['slide'] = $slide;
		}
		$link->URL = $this->webApplication->getPresenter()->link('//:Www:Talks:talk', $params);
		return null;
	}

}
