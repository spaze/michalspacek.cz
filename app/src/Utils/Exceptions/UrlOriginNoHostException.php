<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Utils\Exceptions;

use Throwable;
use Uri\WhatWg\Url;

final class UrlOriginNoHostException extends UrlOriginException
{

	public function __construct(Url $url, ?Throwable $previous = null)
	{
		parent::__construct("URL {$url->toUnicodeString()} does not contain a host part", previous:$previous);
	}

}
