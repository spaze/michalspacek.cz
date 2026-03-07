<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter\TexyPhraseHandler;

use Texy\HandlerInvocation;
use Texy\HtmlElement;
use Texy\Link;
use Texy\Modifier;

final class TexyPhraseHandlerInvocation
{

	public function proceed(HandlerInvocation $invocation, string $phrase, string $content, Modifier $modifier, ?Link $link): HtmlElement|string|null
	{
		return $invocation->proceed($phrase, $content, $modifier, $link);
	}

}
