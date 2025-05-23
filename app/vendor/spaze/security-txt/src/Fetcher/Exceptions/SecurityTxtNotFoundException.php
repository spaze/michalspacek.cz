<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Throwable;

final class SecurityTxtNotFoundException extends SecurityTxtFetcherException
{

	/** @var array<string, array{0:1|134217728, 1:int}> IP address => DNS type, HTTP code */
	private array $ipAddresses;


	/**
	 * @param non-empty-array<string, array{0:string, 1:1|134217728, 2:int}> $urls URL => IP address, DNS record type, HTTP code
	 * @param Throwable|null $previous
	 */
	public function __construct(array $urls, ?Throwable $previous = null)
	{
		$ipAddresses = [];
		foreach ($urls as $ipTypeCode) {
			$ipAddresses[$ipTypeCode[0]] = [$ipTypeCode[1], $ipTypeCode[2]];
		}
		$this->ipAddresses = $ipAddresses;
		parent::__construct(
			[$urls],
			"Can't read `security.txt`: %s",
			[implode(', ', array_map(fn(string $url, array $ipTypeCode): string => "`{$url}` (`{$ipTypeCode[0]}`) => `{$ipTypeCode[2]}`", array_keys($urls), $urls))],
			array_key_first($urls),
			previous: $previous,
		);
	}


	/**
	 * @return array<string, array{0:1|134217728, 1:int}> IP address => DNS type, HTTP code
	 */
	public function getIpAddresses(): array
	{
		return $this->ipAddresses;
	}

}
