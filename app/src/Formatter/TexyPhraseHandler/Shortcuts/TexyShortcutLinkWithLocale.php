<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter\TexyPhraseHandler\Shortcuts;

use Composer\Pcre\Regex;
use MichalSpacekCz\Application\Locale\Locales;
use MichalSpacekCz\Formatter\TexyPhraseHandler\TexyPhraseHandlerLinks;
use Nette\Application\UI\InvalidLinkException;
use Nette\Utils\JsonException;
use Override;
use Texy\HandlerInvocation;
use Texy\Link;
use Texy\Modifier;

final readonly class TexyShortcutLinkWithLocale implements TexyShortcut
{

	public function __construct(
		private TexyPhraseHandlerLinks $handlerLinks,
	) {
	}


	#[Override]
	public function canResolve(string $url): bool
	{
		return str_starts_with($url, 'link-');
	}


	/**
	 * @throws InvalidLinkException
	 * @throws JsonException
	 */
	#[Override]
	public function resolve(string $url, HandlerInvocation $invocation, string $phrase, string $content, Modifier $modifier, Link $link): null
	{
		// "title":[link-en_US:Module:Presenter:action params]
		$result = Regex::matchStrictGroups(sprintf('/^(link-(%s):)(.*)\z/', Locales::LOCALE_REGEXP_SUBSTRING), $url);
		if ($result->matched) {
			$link->URL = $this->handlerLinks->getLink($result->matches[1], $result->matches[3], $result->matches[2]);
		}
		return null;
	}

}
