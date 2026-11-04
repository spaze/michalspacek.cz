<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher;

final readonly class SecurityTxtFetchHostContentType
{

	private const string TOKEN = "[!#$%&'*+.^_`|~0-9a-z-]++";

	private const string QUOTED_STRING = '"(?:[^"\\\\]|\\\\.)*+"';

	/**
	 * A parameter as RFC 9110 section 5.6.6 has it, a name, `=` and a token or a quoted string, or whatever else is up to the next `;` outside a quoted string, which
	 * runs to the end when it isn't closed
	 */
	private const string PARAMETER = '/\G;[ \t]*+(?:(' . self::TOKEN . ')=(' . self::TOKEN . '|' . self::QUOTED_STRING . ')[ \t]*+(?=;|$)|((?:[^;"]++|"(?:[^"\\\\]|\\\\.)*+"?)*+))/isD';

	/**
	 * @var lowercase-string
	 */
	private string $lowercaseContentType;

	private ?string $charsetParameter;

	/**
	 * @var lowercase-string|null
	 */
	private ?string $lowercaseCharsetParameter;


	/**
	 * @param string|null $parameters What follows the first `;`
	 */
	public function __construct(
		private string $contentType,
		?string $parameters,
	) {
		$this->lowercaseContentType = strtolower($this->contentType);
		$charsetParameter = $lowercaseCharsetParameter = null;
		$parameters = ";{$parameters}";
		$offset = 0;
		// Up to the first charset parameter, as one that appears twice is an error, and a parser takes the first
		while ($charsetParameter === null && preg_match(self::PARAMETER, $parameters, $match, 0, $offset) === 1) {
			$offset += strlen($match[0]);
			if (strtolower($match[1]) === 'charset') {
				$charsetParameter = "{$match[1]}={$match[2]}";
				// The quoted and unquoted values are the same
				$charset = str_starts_with($match[2], '"') ? preg_replace('/\\\\(.)/s', '$1', substr($match[2], 1, -1)) : $match[2];
				$lowercaseCharsetParameter = 'charset=' . strtolower($charset ?? '');
			} elseif (isset($match[3]) && preg_match('/^charset(?!' . self::TOKEN . ')/i', $match[3]) === 1) {
				// One that isn't written as RFC 9110 has it, `charset = utf-8` or `charset:utf-8`, is one to change
				$charsetParameter = rtrim($match[3]);
				$lowercaseCharsetParameter = strtolower($charsetParameter);
			}
		}
		$this->charsetParameter = $charsetParameter;
		$this->lowercaseCharsetParameter = $lowercaseCharsetParameter;
	}


	public function getContentType(): string
	{
		return $this->contentType;
	}


	/**
	 * The charset parameter as it was sent
	 */
	public function getCharsetParameter(): ?string
	{
		return $this->charsetParameter;
	}


	/**
	 * @return lowercase-string
	 */
	public function getLowercaseContentType(): string
	{
		return $this->lowercaseContentType;
	}


	/**
	 * The charset parameter all in lowercase, with the value unquoted when it's written as RFC 9110 has it
	 *
	 * @return lowercase-string|null
	 */
	public function getLowercaseCharsetParameter(): ?string
	{
		return $this->lowercaseCharsetParameter;
	}

}
