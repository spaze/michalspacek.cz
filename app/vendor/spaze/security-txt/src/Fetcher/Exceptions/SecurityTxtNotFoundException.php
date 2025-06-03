<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Throwable;

final class SecurityTxtNotFoundException extends SecurityTxtFetcherException
{

	/** @var array<string, array{0:1|134217728, 1:int}> IP address => DNS type, HTTP code */
	private array $ipAddresses = [];

	/** @var array<string, list<string>> original URL => redirects */
	private array $allRedirects = [];


	/**
	 * @param non-empty-array<string, array{0:string, 1:1|134217728, 2:int, 3:list<string>, 4:bool}> $urls URL => 0: IP address, 1: DNS record type, 2: HTTP code, 3: redirects, 4: is regular HTML page?
	 * @param Throwable|null $previous
	 */
	public function __construct(array $urls, ?Throwable $previous = null)
	{
		$message = "Can't read %s: ";
		$messageValues = ['security.txt'];
		foreach ($urls as $url => $components) {
			if ($this->ipAddresses !== []) {
				$message .= ', '; // Not added in the first iteration
			}
			$message .= $components[4] ? '%s (%s) => regular HTML page' : '%s (%s) => %s';
			$messageValues[] = $url;
			$messageValues[] = $components[0];
			if (!$components[4]) {
				$messageValues[] = (string)$components[2];
			}
			$this->ipAddresses[$components[0]] = [$components[1], $components[2]];
			if ($components[3] !== []) {
				$this->allRedirects[$url] = $components[3];
				$message .= $components[4] ? ' (final page after redirects)' : ' (final code after redirects)';
			}
		}
		parent::__construct([$urls], $message, $messageValues, array_key_first($urls), previous: $previous);
	}


	/**
	 * @return array<string, array{0:1|134217728, 1:int}> IP address => DNS type, HTTP code
	 */
	public function getIpAddresses(): array
	{
		return $this->ipAddresses;
	}


	/**
	 * @return array<string, list<string>> original URL => redirects
	 */
	public function getAllRedirects(): array
	{
		return $this->allRedirects;
	}

}
