<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter\TexyPhraseHandler;

use MichalSpacekCz\Formatter\Exceptions\UnexpectedHandlerInvocationReturnTypeException;
use Texy\HandlerInvocation;
use Texy\HtmlElement;
use Texy\Link;
use Texy\Modifier;

final class TexyPhraseHandlerInvocation
{

	/**
	 * @throws UnexpectedHandlerInvocationReturnTypeException
	 */
	public function proceed(HandlerInvocation $invocation, string $phrase, string $content, Modifier $modifier, ?Link $link): HtmlElement|string|false
	{
		$result = $invocation->proceed($phrase, $content, $modifier, $link);
		if (!$result instanceof HtmlElement && !is_string($result) && $result !== false) {
			throw new UnexpectedHandlerInvocationReturnTypeException($result);
		}
		return $result;
	}

}
