<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use MichalSpacekCz\DateTime\Exceptions\CannotParseDateTimeException;
use MichalSpacekCz\Http\Client\HttpClient;
use MichalSpacekCz\Net\DnsResolver;
use MichalSpacekCz\Net\Exceptions\DnsGetRecordException;
use MichalSpacekCz\Tls\Exceptions\CertificateException;
use MichalSpacekCz\Tls\Exceptions\OpenSslException;
use MichalSpacekCz\Tls\Exceptions\OpenSslX509ParseException;

class CertificateGatherer
{

	public function __construct(
		private readonly CertificateFactory $certificateFactory,
		private readonly HttpClient $httpClient,
		private readonly DnsResolver $dnsResolver,
	) {
	}


	/**
	 * @param string $hostname
	 * @param bool $includeIpv6
	 * @return array<string, Certificate>
	 * @throws CannotParseDateTimeException
	 * @throws CertificateException
	 * @throws OpenSslException
	 * @throws DnsGetRecordException
	 * @throws OpenSslX509ParseException
	 */
	public function fetchCertificates(string $hostname, bool $includeIpv6): array
	{
		$certificates = [];
		$records = $this->dnsResolver->getRecords($hostname, $includeIpv6 ? DNS_A | DNS_AAAA : DNS_A);
		foreach ($records as $record) {
			if ($record->getIpv6() !== null) {
				$ipAddress = "[{$record->getIpv6()}]";
			} elseif ($record->getIp() !== null) {
				$ipAddress = $record->getIp();
			}
			if (!isset($ipAddress)) {
				throw new DnsGetRecordException("No IPv4/v6 address for {$hostname}");
			}
			$certificates[$ipAddress] = $this->fetchCertificate($hostname, $ipAddress);
		}
		return $certificates;
	}


	/**
	 * @throws OpenSslException
	 * @throws CertificateException
	 * @throws CannotParseDateTimeException
	 * @throws OpenSslX509ParseException
	 */
	private function fetchCertificate(string $hostname, string $ipAddress): Certificate
	{
		$url = "https://{$ipAddress}/";
		$fp = fopen($url, 'r', context: $this->httpClient->createStreamContext(
			__METHOD__,
			[
				'method' => 'HEAD',
				'follow_location' => 0,
			],
			[
				"Host: {$hostname}",
			],
			[
				'capture_peer_cert' => true,
				'peer_name' => $hostname,
			],
		));
		if (!$fp) {
			throw new CertificateException("Unable to open {$url}");
		}
		$options = stream_context_get_options($fp);
		fclose($fp);
		return $this->certificateFactory->fromObject($options['ssl']['peer_certificate']);
	}

}
