<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt;

final readonly class SecurityTxtContentType
{

	public const string CONTENT_TYPE = 'text/plain';
	public const string CHARSET = 'utf-8';
	public const string CHARSET_PARAMETER = 'charset=' . self::CHARSET;
	public const string MEDIA_TYPE = self::CONTENT_TYPE . '; ' . self::CHARSET_PARAMETER;

}
