<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use MichalSpacekCz\DateTime\Exceptions\CannotParseDateTimeException;
use MichalSpacekCz\Http\Client\HttpClient;
use MichalSpacekCz\Http\Client\HttpClientRequest;
use MichalSpacekCz\Http\Exceptions\HttpClientRequestException;
use MichalSpacekCz\Http\Exceptions\HttpClientTlsCertificateNotAvailableException;
use MichalSpacekCz\Http\Exceptions\HttpClientTlsCertificateNotCapturedException;
use MichalSpacekCz\Net\DnsResolver;
use MichalSpacekCz\Net\Exceptions\DnsGetRecordException;
use MichalSpacekCz\Tls\Exceptions\OpenSslException;
use MichalSpacekCz\Tls\Exceptions\OpenSslX509ParseException;

final readonly class CertificateGatherer
{

	public function __construct(
		private CertificateFactory $certificateFactory,
		private HttpClient $httpClient,
		private DnsResolver $dnsResolver,
	) {
	}


	/**
	 * @param string $hostname
	 * @param bool $includeIpv6
	 * @return array<string, Certificate>
	 * @throws CannotParseDateTimeException
	 * @throws OpenSslException
	 * @throws DnsGetRecordException
	 * @throws OpenSslX509ParseException
	 * @throws HttpClientRequestException
	 * @throws HttpClientTlsCertificateNotAvailableException
	 * @throws HttpClientTlsCertificateNotCapturedException
	 */
	public function fetchCertificates(string $hostname, bool $includeIpv6): array
	{
		$certificates = [];
		$records = $this->dnsResolver->getRecords($hostname, $includeIpv6 ? DNS_A | DNS_AAAA : DNS_A);
		foreach ($records as $record) {
			$ipAddress = null;
			if ($record->getIpv6() !== null) {
				$ipAddress = "[{$record->getIpv6()}]";
			} elseif ($record->getIp() !== null) {
				$ipAddress = $record->getIp();
			}
			if ($ipAddress === null) {
				throw new DnsGetRecordException("No IPv4/v6 address for {$hostname}");
			}
			$certificates[$ipAddress] = $this->fetchCertificate($hostname, $ipAddress);
		}
		return $certificates;
	}


	/**
	 * @throws OpenSslException
	 * @throws CannotParseDateTimeException
	 * @throws OpenSslX509ParseException
	 * @throws HttpClientRequestException
	 * @throws HttpClientTlsCertificateNotAvailableException
	 * @throws HttpClientTlsCertificateNotCapturedException
	 */
	private function fetchCertificate(string $hostname, string $ipAddress): Certificate
	{
		$request = new HttpClientRequest("https://{$ipAddress}/");
		$request->setUserAgent(__METHOD__);
		$request->setFollowLocation(false);
		$request->addHeader('Host', $hostname);
		$request->setTlsCaptureCertificate(true);
		$request->setTlsServerName($hostname);
		return $this->certificateFactory->fromObject($hostname, $this->httpClient->head($request)->getTlsCertificate());
	}

}
