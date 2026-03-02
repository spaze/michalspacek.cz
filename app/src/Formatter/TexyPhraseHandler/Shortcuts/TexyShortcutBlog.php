<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter\TexyPhraseHandler\Shortcuts;

use Contributte\Translation\Translator;
use MichalSpacekCz\Formatter\TexyPhraseHandler\TexyPhraseHandlerLinks;
use Nette\Application\UI\InvalidLinkException;
use Nette\Utils\JsonException;
use Override;
use Texy\HandlerInvocation;
use Texy\Link;
use Texy\Modifier;

final readonly class TexyShortcutBlog implements TexyShortcut
{

	private const string PREFIX = 'blog:';


	public function __construct(
		private Translator $translator,
		private TexyPhraseHandlerLinks $handlerLinks,
	) {
	}


	#[Override]
	public function canResolve(string $url): bool
	{
		return str_starts_with($url, self::PREFIX);
	}


	/**
	 * @throws InvalidLinkException
	 * @throws JsonException
	 */
	#[Override]
	public function resolve(string $url, HandlerInvocation $invocation, string $phrase, string $content, Modifier $modifier, Link $link): null
	{
		// "title":[blog:post#fragment]
		$link->URL = $this->handlerLinks->getBlogLink(self::PREFIX, substr($url, strlen(self::PREFIX)), $this->translator->getDefaultLocale());
		return null;
	}

}
