<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Exception;
use JsonSerializable;
use Override;
use Spaze\SecurityTxt\Fetcher\SecurityTxtRedirects;
use Spaze\SecurityTxt\Json\SecurityTxtJsonValue;
use Spaze\SecurityTxt\SecurityTxtHost;
use Spaze\SecurityTxt\SecurityTxtPrintableValue;
use Throwable;
use Uri\WhatWg\Url;

abstract class SecurityTxtFetcherException extends Exception implements JsonSerializable
{

	/** @var list<string|Url|SecurityTxtHost> */
	private readonly array $messageValues;


	/**
	 * @param list<scalar|null|Url|SecurityTxtHost|SecurityTxtRedirects|array<array-key, scalar|array<array-key, scalar|list<string>>>> $constructorParams Passed as themselves, a URL and a host are put in the spelling the wire carries when this is serialized and not before, so no caller has to know one
	 * @param literal-string $messageFormat Never build this from anything the checked host sends, it is used as a format and only the values are encoded when printed
	 * @param array<array-key, string|Url|SecurityTxtHost> $messageValues A host and a URL are passed as themselves so each prints as it reads, like everywhere else. Stored as a list, see the constructor
	 * @param Url|null $url Null where there is none to name, which is what a hostname that would not parse leaves behind. Nullable but not optional, so a subclass with a URL to hand over cannot leave it out by saying nothing
	 * @param SecurityTxtRedirects $redirects Where the check was sent, empty when it was not
	 * @throws Throwable
	 */
	public function __construct(
		private readonly array $constructorParams,
		private readonly string $messageFormat,
		array $messageValues,
		private readonly ?Url $url,
		private readonly SecurityTxtRedirects $redirects = new SecurityTxtRedirects(),
		int $code = 0,
		?Throwable $previous = null,
	) {
		// Code always passes a list, but `SecurityTxtJson` replays whatever the serialized params hold, and a string key there is read as a named argument by the CLI, which
		// spreads these into a call
		$this->messageValues = array_values($messageValues);
		// `Exception::getMessage()` is final, so this is the only place the message can be made safe to display anywhere, terminal, log or page alike; `getMessageValues()`
		// still hands over what the host sent, for a caller that knows what it is rendering into
		parent::__construct(vsprintf($this->messageFormat, array_map(fn(string|Url|SecurityTxtHost $value): string => new SecurityTxtPrintableValue($value)->render(), $this->messageValues)), $code, $previous);
	}


	/**
	 * @return literal-string
	 */
	public function getMessageFormat(): string
	{
		return $this->messageFormat;
	}


	/**
	 * The ` (redirects: %s → %s)` part of a message, with one placeholder per redirect, empty when there was none.
	 *
	 * @param literal-string $suffix Added inside the brackets after the last redirect
	 * @return literal-string
	 */
	protected function getRedirectsFormat(SecurityTxtRedirects $redirects, string $suffix = ''): string
	{
		$count = $redirects->count();
		if ($count === 0) {
			return '';
		}
		$format = ' (redirects: %s';
		for ($i = 1; $i < $count; $i++) {
			$format .= ' → %s';
		}
		return $format . $suffix . ')';
	}


	/**
	 * @return list<string|Url|SecurityTxtHost>
	 */
	public function getMessageValues(): array
	{
		return $this->messageValues;
	}


	public function getUrl(): ?Url
	{
		return $this->url;
	}


	public function getRedirects(): SecurityTxtRedirects
	{
		return $this->redirects;
	}


	/**
	 * The one place a stored URL or host is spelled. `SecurityTxtJson` reads the constructor back and turns the string into the object again, so what a subclass hands over is
	 * what its own constructor takes, and a spelling nobody writes is a spelling nobody can get wrong.
	 *
	 * @return array<string, mixed>
	 */
	#[Override]
	public function jsonSerialize(): array
	{
		return [
			'class' => $this::class,
			'params' => array_map(fn(mixed $param): string|int|float|bool|array|null => new SecurityTxtJsonValue($param)->toValue(), $this->constructorParams),
		];
	}

}
