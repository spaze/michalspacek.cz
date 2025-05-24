<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Throwable;

final class SecurityTxtNotFoundException extends SecurityTxtFetcherException
{

	/** @var array<string, array{0:1|134217728, 1:int}> IP address => DNS type, HTTP code */
	private array $ipAddresses = [];

	/** @var array<string, list<string>> original URL => redirects */
	private array $urlRedirects = [];


	/**
	 * @param non-empty-array<string, array{0:string, 1:1|134217728, 2:int, 3:list<string>}> $urls URL => IP address, DNS record type, HTTP code, redirects
	 * @param Throwable|null $previous
	 */
	public function __construct(array $urls, ?Throwable $previous = null)
	{
		foreach ($urls as $url => $components) {
			$this->ipAddresses[$components[0]] = [$components[1], $components[2]];
			if ($components[3] !== []) {
				$this->urlRedirects[$url] = $components[3];
			}
		}
		parent::__construct(
			[$urls],
			"Can't read `security.txt`: %s",
			[implode(', ', array_map($this->formatUrls(...), array_keys($urls), $urls))],
			array_key_first($urls),
			previous: $previous,
		);
	}


	/**
	 * @param array{0:string, 1:1|134217728, 2:int, 3:list<string>} $components
	 */
	private function formatUrls(string $url, array $components): string
	{
		$message = "`{$url}` (`{$components[0]}`) => `{$components[2]}`";
		return isset($this->urlRedirects[$url]) && $this->urlRedirects[$url] !== [] ? "{$message} (final code after redirects)" : $message;
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
	public function getUrlRedirects(): array
	{
		return $this->urlRedirects;
	}

}
