<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter\TexyPhraseHandler\Shortcuts;

use MichalSpacekCz\Formatter\Exceptions\UnexpectedHandlerInvocationReturnTypeException;
use Nette\Application\UI\InvalidLinkException;
use Nette\Utils\JsonException;
use Texy\HandlerInvocation;
use Texy\HtmlElement;
use Texy\Link;
use Texy\Modifier;

interface TexyShortcut
{

	public function canResolve(string $url): bool;


	/**
	 * @throws InvalidLinkException
	 * @throws JsonException
	 * @throws UnexpectedHandlerInvocationReturnTypeException
	 */
	public function resolve(string $url, HandlerInvocation $invocation, string $phrase, string $content, Modifier $modifier, Link $link): ?HtmlElement;

}
