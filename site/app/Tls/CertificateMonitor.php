<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use JakubOnderka\PhpConsoleColor\ConsoleColor;

class CertificateMonitor
{

	private bool $hasErrors = false;


	public function __construct(
		private CertificateGatherer $certificateGatherer,
		private CertificatesApiClient $certificatesApiClient,
		private ConsoleColor $color,
	) {
	}


	public function run(): never
	{
		// Not running in parallel because those sites are hosted on just a few tiny servers
		foreach ($this->certificatesApiClient->getLoggedCertificates() as $loggedCertificate) {
			$this->compareCertificates($loggedCertificate, $this->certificateGatherer->fetchCertificate($loggedCertificate->getCommonName()));
		}
		exit($this->hasErrors ? 1 : 0);
	}


	private function compareCertificates(Certificate $loggedCertificate, Certificate $serverCertificate): void
	{
		$error = false;
		if ($loggedCertificate->getNotAfter()->getTimestamp() !== $serverCertificate->getNotAfter()->getTimestamp()) {
			$error = true;
			$this->error($serverCertificate, sprintf(
				"Logged certificate's notAfter (%s, %s) doesn't match server certificate's notAfter (%s, %s)",
				$loggedCertificate->getNotAfter()->format(DATE_RFC3339),
				$this->getExpiryDays($loggedCertificate),
				$serverCertificate->getNotAfter()->format(DATE_RFC3339),
				$this->getExpiryDays($serverCertificate),
			));
		}
		if ($serverCertificate->isExpiringSoon()) {
			$error = true;
			$this->error($serverCertificate, sprintf(
				'Server certificate expires soon (notAfter: %s, %s)',
				$serverCertificate->getNotAfter()->format(DATE_RFC3339),
				$this->getExpiryDays($serverCertificate),
			));
		}
		if ($serverCertificate->isExpired()) {
			$error = true;
			$this->error($serverCertificate, sprintf(
				'Server certificate expired (notAfter: %s, %s)',
				$serverCertificate->getNotAfter()->format(DATE_RFC3339),
				$this->getExpiryDays($serverCertificate),
			));
		}
		if ($error) {
			$this->hasErrors = true;
		} else {
			$this->info($loggedCertificate, sprintf(
				"Logged certificate's notAfter matches server certificate's notAfter (%s, %s)",
				$loggedCertificate->getNotAfter()->format(DATE_RFC3339),
				$this->getExpiryDays($loggedCertificate),
			));
		}
	}


	private function getExpiryDays(Certificate $certificate): string
	{
		$days = '';
		if (!$certificate->isExpired()) {
			$days .= 'in ';
		}
		$days .= $certificate->getExpiryDays() . ' ';
		$days .= $certificate->getExpiryDays() === 1 ? 'day' : 'days';
		if ($certificate->isExpired()) {
			$days .= ' ago';
		}
		return $days;
	}


	private function error(Certificate $certificate, string $message): void
	{
		$this->message(
			$certificate,
			$this->color->apply('dark_gray', '%s') . ' ' . $this->color->apply(['light_red', 'bold'], '%s') . ' ' . $this->color->apply('light_red', 'ERROR:') . ' %s',
			$message,
		);
	}


	private function info(Certificate $certificate, string $message): void
	{
		$this->message(
			$certificate,
			$this->color->apply('dark_gray', '%s') . ' ' . $this->color->apply(['light_green', 'bold'], '%s') . ' ' . $this->color->apply('light_green', 'INFO:') . ' %s',
			$message,
		);
	}


	private function message(Certificate $certificate, string $format, string $message): void
	{
		printf(
			$format . "\n",
			date(DATE_RFC3339),
			$certificate->getCommonName(),
			$message,
		);
	}

}
