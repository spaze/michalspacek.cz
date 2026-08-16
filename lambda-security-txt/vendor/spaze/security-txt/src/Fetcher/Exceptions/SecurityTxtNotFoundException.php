<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Spaze\SecurityTxt\Fetcher\SecurityTxtIpAddressType;
use Throwable;

final class SecurityTxtNotFoundException extends SecurityTxtFetcherException
{

	/** @var array<string, array{0:value-of<SecurityTxtIpAddressType>, 1:int}> IP address => DNS type, HTTP code */
	private array $ipAddresses = [];

	/** @var array<string, list<string>> original URL => redirects */
	private array $allRedirects = [];


	/**
	 * @param array<array-key, mixed>|non-empty-array<string, array{ip:string, type:value-of<SecurityTxtIpAddressType>, code:int, redirects:list<string>, html:bool, truncated:bool}> $securityTxtUrls URL => IP address, IP address type, HTTP code, redirects, regular HTML page?, response too long?
	 * @throws SecurityTxtNotFoundWrongUrlStructureException
	 */
	public function __construct(array $securityTxtUrls, string $wellKnownUrl, ?Throwable $previous = null)
	{
		$message = "Can't read %s: ";
		$messageValues = ['security.txt'];
		$urls = [];
		if ($securityTxtUrls === []) {
			throw new SecurityTxtNotFoundWrongUrlStructureException('securityTxtUrls is empty');
		}
		if (!array_key_exists($wellKnownUrl, $securityTxtUrls)) {
			throw new SecurityTxtNotFoundWrongUrlStructureException("securityTxtUrls does not contain the well-known URL {$wellKnownUrl}");
		}
		foreach ($securityTxtUrls as $url => $components) {
			if (!is_string($url)) {
				throw new SecurityTxtNotFoundWrongUrlStructureException('securityTxtUrls key is not a string');
			}
			if (!is_array($components)) {
				throw new SecurityTxtNotFoundWrongUrlStructureException("securityTxtUrls > {$url} is not an array");
			}
			if (!isset($components['ip']) || !is_string($components['ip'])) {
				throw new SecurityTxtNotFoundWrongUrlStructureException("securityTxtUrls > {$url} > ip is not set or not a string");
			}
			if (!isset($components['type']) || !is_int($components['type'])) {
				throw new SecurityTxtNotFoundWrongUrlStructureException("securityTxtUrls > {$url} > type is not set or not an int");
			}
			$type = SecurityTxtIpAddressType::tryFrom($components['type']);
			if ($type === null) {
				throw new SecurityTxtNotFoundWrongUrlStructureException("securityTxtUrls > {$url} > type is not a value of " . SecurityTxtIpAddressType::class);
			}
			if (!isset($components['code']) || !is_int($components['code'])) {
				throw new SecurityTxtNotFoundWrongUrlStructureException("securityTxtUrls > {$url} > code is not set or not an int");
			}
			if (!isset($components['redirects']) || !is_array($components['redirects'])) {
				throw new SecurityTxtNotFoundWrongUrlStructureException("securityTxtUrls > {$url} > redirects is not set or not an array");
			}
			$redirects = [];
			foreach ($components['redirects'] as $key => $redirect) {
				if (!is_string($redirect)) {
					throw new SecurityTxtNotFoundWrongUrlStructureException("securityTxtUrls > {$url} > redirects > {$key} is not a string");
				}
				$redirects[] = $redirect;
			}
			if (!isset($components['html']) || !is_bool($components['html'])) {
				throw new SecurityTxtNotFoundWrongUrlStructureException("securityTxtUrls > {$url} > html is not set or not a bool");
			}
			if (!isset($components['truncated']) || !is_bool($components['truncated'])) {
				throw new SecurityTxtNotFoundWrongUrlStructureException("securityTxtUrls > {$url} > truncated is not set or not a bool");
			}
			$urls[$url] = [
				'ip' => $components['ip'],
				'type' => $type->value,
				'code' => $components['code'],
				'redirects' => $redirects,
				'html' => $components['html'],
				'truncated' => $components['truncated'],
			];

			if ($this->ipAddresses !== []) {
				$message .= ', '; // Not added in the first iteration
			}
			if ($components['truncated'] && $components['html']) {
				$message .= '%s (%s) => regular HTML page and too long';
			} elseif ($components['truncated']) {
				$message .= '%s (%s) => response too long';
			} elseif ($components['html']) {
				$message .= '%s (%s) => regular HTML page';
			} else {
				$message .= '%s (%s) => %s';
			}
			$messageValues[] = $url;
			$messageValues[] = $components['ip'];
			if (!$components['html'] && !$components['truncated']) {
				$messageValues[] = (string)$components['code'];
			}
			$this->ipAddresses[$components['ip']] = [$type->value, $components['code']];
			if ($redirects !== []) {
				$this->allRedirects[$url] = $redirects;
				$message .= $components['html'] || $components['truncated'] ? ' (final page after redirects)' : ' (final code after redirects)';
			}
		}
		parent::__construct([$urls, $wellKnownUrl], $message, $messageValues, $wellKnownUrl, previous: $previous);
	}


	/**
	 * @return array<string, array{0:value-of<SecurityTxtIpAddressType>, 1:int}> IP address => DNS type, HTTP code
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
