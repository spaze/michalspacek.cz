<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use Exception;
use MichalSpacekCz\Application\Cli\CliArgs;
use MichalSpacekCz\Application\Cli\CliArgsProvider;
use Override;
use PHP_Parallel_Lint\PhpConsoleColor\ConsoleColor;

final class CertificateMonitor implements CliArgsProvider
{

	private const string NO_IPV6 = '--no-ipv6';

	private bool $hasErrors = false;


	public function __construct(
		private readonly CertificateGatherer $certificateGatherer,
		private readonly CertificatesApiClient $certificatesApiClient,
		private readonly ConsoleColor $color,
		private readonly CliArgs $cliArgs,
	) {
	}


	public function run(): never
	{
		$cliArgsError = $this->cliArgs->getError();
		if ($cliArgsError !== null) {
			$this->error(null, $cliArgsError);
			exit(3);
		}
		try {
			// Not running in parallel because those sites are hosted on just a few tiny servers
			foreach ($this->certificatesApiClient->getLoggedCertificates() as $loggedCertificate) {
				$this->compareCertificates($loggedCertificate, $this->certificateGatherer->fetchCertificates($loggedCertificate->getCertificateName(), !$this->cliArgs->getFlag(self::NO_IPV6)));
			}
			exit($this->hasErrors ? 1 : 0);
		} catch (Exception $e) {
			$this->error(null, $e->getMessage());
			exit(2);
		}
	}


	/**
	 * @param Certificate $loggedCertificate
	 * @param array<string, Certificate> $serverCertificates
	 * @return void
	 */
	private function compareCertificates(Certificate $loggedCertificate, array $serverCertificates): void
	{
		foreach ($serverCertificates as $ipAddress => $serverCertificate) {
			$this->compareCertificate($loggedCertificate, $serverCertificate, $ipAddress);
		}
	}


	private function compareCertificate(Certificate $loggedCertificate, Certificate $serverCertificate, string $ipAddress): void
	{
		$error = false;
		if ($loggedCertificate->getNotAfter()->getTimestamp() !== $serverCertificate->getNotAfter()->getTimestamp()) {
			$error = true;
			$this->error($serverCertificate, sprintf(
				"Logged certificate's notAfter (%s, %s) doesn't match %s certificate's notAfter (%s, %s)",
				$loggedCertificate->getNotAfter()->format(DATE_RFC3339),
				$this->getExpiryDays($loggedCertificate),
				$ipAddress,
				$serverCertificate->getNotAfter()->format(DATE_RFC3339),
				$this->getExpiryDays($serverCertificate),
			));
		}
		if ($serverCertificate->isExpiringSoon()) {
			$error = true;
			$this->error($serverCertificate, sprintf(
				'%s certificate expires soon (notAfter: %s, %s)',
				$ipAddress,
				$serverCertificate->getNotAfter()->format(DATE_RFC3339),
				$this->getExpiryDays($serverCertificate),
			));
		}
		if ($serverCertificate->isExpired()) {
			$error = true;
			$this->error($serverCertificate, sprintf(
				'%s certificate expired (notAfter: %s, %s)',
				$ipAddress,
				$serverCertificate->getNotAfter()->format(DATE_RFC3339),
				$this->getExpiryDays($serverCertificate),
			));
		}
		if ($error) {
			$this->hasErrors = true;
		} else {
			$this->info($loggedCertificate, sprintf(
				"Logged certificate's notAfter matches %s certificate's notAfter (%s, %s)",
				$ipAddress,
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


	private function error(?Certificate $certificate, string $message): void
	{
		$this->message(
			$certificate,
			$this->color->apply('dark_gray', '%s') . ' ' . $this->color->apply(['light_red', 'bold'], '%s') . ' ' . $this->color->apply('light_red', 'ERROR:') . ' %s',
			$message,
		);
	}


	private function info(?Certificate $certificate, string $message): void
	{
		$this->message(
			$certificate,
			$this->color->apply('dark_gray', '%s') . ' ' . $this->color->apply(['light_green', 'bold'], '%s') . ' ' . $this->color->apply('light_green', 'INFO:') . ' %s',
			$message,
		);
	}


	private function message(?Certificate $certificate, string $format, string $message): void
	{
		printf(
			$format . "\n",
			date(DATE_RFC3339),
			$certificate !== null ? $certificate->getCertificateName() : __CLASS__,
			$message,
		);
	}


	#[Override]
	public static function getArgs(): array
	{
		return [self::NO_IPV6];
	}


	#[Override]
	public static function getPositionalArgs(): array
	{
		return [];
	}

}
