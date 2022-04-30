<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use MichalSpacekCz\DateTime\Exceptions\CannotParseDateTimeException;
use MichalSpacekCz\Http\HttpStreamContext;
use MichalSpacekCz\Net\DnsResolver;
use MichalSpacekCz\Net\Exceptions\DnsGetRecordException;
use MichalSpacekCz\Tls\Exceptions\CertificateException;
use MichalSpacekCz\Tls\Exceptions\OpenSslException;

class CertificateGatherer
{

	public function __construct(
		private CertificateFactory $certificateFactory,
		private HttpStreamContext $httpStreamContext,
		private DnsResolver $dnsResolver,
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
	 */
	public function fetchCertificates(string $hostname, bool $includeIpv6): array
	{
		$certificates = [];
		$records = $this->dnsResolver->getRecords($hostname, $includeIpv6 ? DNS_A | DNS_AAAA : DNS_A);
		foreach ($records as $record) {
			$certificates[$record->getIpv6() ?? $record->getIp()] = $this->fetchCertificate($hostname, $record->getIp() ?? null, $record->getIpv6() ?? null);
		}
		return $certificates;
	}


	/**
	 * @throws OpenSslException
	 * @throws CertificateException
	 * @throws CannotParseDateTimeException
	 */
	private function fetchCertificate(string $hostname, ?string $ipv4, ?string $ipv6): Certificate
	{
		$url = 'https://' . ($ipv6 ? "[{$ipv6}]" : $ipv4) . '/';
		$fp = fopen($url, 'r', context: $this->httpStreamContext->create(
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
