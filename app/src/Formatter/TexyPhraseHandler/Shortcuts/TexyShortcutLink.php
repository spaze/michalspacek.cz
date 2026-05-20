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

final readonly class TexyShortcutLink implements TexyShortcut
{

	public const string SCHEME = 'link';


	public function __construct(
		private Translator $translator,
		private TexyPhraseHandlerLinks $handlerLinks,
	) {
	}


	#[Override]
	public function canResolve(string $url): bool
	{
		return str_starts_with($url, self::SCHEME . ':');
	}


	/**
	 * @throws InvalidLinkException
	 * @throws JsonException
	 */
	#[Override]
	public function resolve(string $url, HandlerInvocation $invocation, string $phrase, string $content, Modifier $modifier, Link $link): null
	{
		// "title":[link:Module:Presenter:action params]
		$prefix = self::SCHEME . ':';
		$link->URL = $this->handlerLinks->getLink($prefix, substr($url, strlen($prefix)), $this->translator->getDefaultLocale());
		return null;
	}

}
