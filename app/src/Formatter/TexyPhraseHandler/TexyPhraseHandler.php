<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter\TexyPhraseHandler;

use MichalSpacekCz\Formatter\Exceptions\UnexpectedHandlerInvocationReturnTypeException;
use MichalSpacekCz\Formatter\TexyPhraseHandler\Shortcuts\TexyShortcut;
use Nette\Application\UI\InvalidLinkException;
use Nette\Utils\JsonException;
use Texy\HandlerInvocation;
use Texy\HtmlElement;
use Texy\Link;
use Texy\Modifier;

final readonly class TexyPhraseHandler
{

	/**
	 * @param list<TexyShortcut> $shortcuts
	 */
	public function __construct(
		private TexyPhraseHandlerInvocation $handlerInvocation,
		private array $shortcuts,
	) {
	}


	/**
	 * @throws InvalidLinkException
	 * @throws JsonException
	 * @throws UnexpectedHandlerInvocationReturnTypeException
	 */
	public function solve(HandlerInvocation $invocation, string $phrase, string $content, Modifier $modifier, ?Link $link): HtmlElement|string|false
	{
		if ($link === null) {
			return $this->handlerInvocation->proceed($invocation, $phrase, $content, $modifier, $link);
		}

		$url = $link->URL ?? $link->raw;
		foreach ($this->shortcuts as $shortcut) {
			if ($shortcut->canResolve($url)) {
				$html = $shortcut->resolve($url, $invocation, $phrase, $content, $modifier, $link);
				if ($html !== null) {
					return $html;
				}
			}
		}
		return $this->handlerInvocation->proceed($invocation, $phrase, $content, $modifier, $link);
	}

}
