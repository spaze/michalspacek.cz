<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Spaze\SecurityTxt\Fetcher\SecurityTxtFetcherFetchHostResult;
use Throwable;

final class SecurityTxtNotFoundException extends SecurityTxtFetcherException
{

	/** @var array<string, array{0:1|134217728, 1:int}> IP address => DNS type, HTTP code */
	private array $ipAddresses = [];

	/** @var array<string, list<string>> original URL => redirects */
	private array $allRedirects = [];


	/**
	 * @param non-empty-list<SecurityTxtFetcherFetchHostResult> $results
	 * @param array<string, list<string>> $observedRedirects
	 * @param Throwable|null $previous
	 */
	public function __construct(array $results, array $observedRedirects, ?Throwable $previous = null)
	{
		$message = "Can't read %s: ";
		$messageValues = ['security.txt'];

		$urls = [];
		foreach ($results as $result) {
			$urls[] = $result->getUrl();
			if ($this->ipAddresses !== []) {
				$message .= ', '; // Not added in the first iteration
			}
			if ($result->isTruncated() && $result->isRegularHtmlPage()) {
				$message .= '%s (%s) => regular HTML page and too long';
			} elseif ($result->isTruncated()) {
				$message .= '%s (%s) => response too long';
			} elseif ($result->isRegularHtmlPage()) {
				$message .= '%s (%s) => regular HTML page';
			} else {
				$message .= '%s (%s) => %s';
			}
			$messageValues[] = $result->getUrl();
			$messageValues[] = $result->getIpAddress();
			if (!$result->isRegularHtmlPage() && !$result->isTruncated()) {
				$messageValues[] = (string)$result->getHttpCode();
			}
			$this->ipAddresses[$result->getIpAddress()] = [$result->getIpAddressType(), $result->getHttpCode()];
			$redirects = $observedRedirects[$result->getUrl()] ?? [];
			if ($redirects !== []) {
				$this->allRedirects[$result->getUrl()] = $redirects;
				$message .= $result->isRegularHtmlPage() || $result->isTruncated() ? ' (final page after redirects)' : ' (final code after redirects)';
			}
		}
		parent::__construct([$urls], $message, $messageValues, $urls[0], previous: $previous);
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
