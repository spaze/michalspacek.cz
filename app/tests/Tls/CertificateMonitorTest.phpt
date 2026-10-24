<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use DateTimeImmutable;
use MichalSpacekCz\Application\Cli\CliArgs;
use MichalSpacekCz\Net\DnsRecord;
use MichalSpacekCz\Test\Http\Client\HttpClientMock;
use MichalSpacekCz\Test\Net\DnsResolverMock;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Test\Tls\SelfSignedCertificate;
use Nette\Utils\Json;
use OpenSSLCertificate;
use Override;
use PHP_Parallel_Lint\PhpConsoleColor\ConsoleColor;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class CertificateMonitorTest extends TestCase
{

	private const string NAME = 'example.com';
	private const string ADDRESS = '192.0.2.1';


	public function __construct(
		private readonly CertificateFactory $certificateFactory,
		private readonly CertificatesApiClient $certificatesApiClient,
		private readonly HttpClientMock $httpClient,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->httpClient->reset();
	}


	public function testMatchingCertificateIsReportedAndTheRunSucceeds(): void
	{
		$served = $this->issue(10);
		[$code, $output] = $this->runWithLogged($served, $this->notAfter($served));
		Assert::same(0, $code);
		Assert::contains(self::NAME . " INFO: Logged certificate's notAfter matches " . self::ADDRESS . " certificate's notAfter", $output);
	}


	public function testApiRefusalStopsTheRun(): void
	{
		$this->httpClient->setStatusCode(403);
		$this->httpClient->setResponse('{"message":"Invalid credentials"}');
		[$code, $output] = $this->runMonitor($this->issue(10), new CliArgs([], null));
		Assert::same(2, $code);
		Assert::contains('ERROR: Response from', $output);
		Assert::contains('not ok, HTTP 403: Invalid credentials', $output);
	}


	public function testBadArgumentsStopTheRunBeforeAnything(): void
	{
		[$code, $output] = $this->runMonitor($this->issue(10), new CliArgs([], 'Unknown option --frobnicate'));
		Assert::same(3, $code);
		Assert::contains('ERROR: Unknown option --frobnicate', $output);
		Assert::count(0, $this->httpClient->getRequests());
	}


	/**
	 * @return array{0: int, 1: string} The exit status and the printed report
	 */
	private function runWithLogged(OpenSSLCertificate $served, DateTimeImmutable $loggedNotAfter): array
	{
		$logged = new Certificate(self::NAME, null, self::NAME, [self::NAME], $loggedNotAfter->modify('-6 days'), $loggedNotAfter, null, new DateTimeImmutable());
		$this->httpClient->setResponse(Json::encode(['message' => 'OK', 'certificates' => [$logged]]));
		return $this->runMonitor($served, new CliArgs(['--no-ipv6' => true], null));
	}


	/**
	 * @return array{0: int, 1: string} The exit status and the printed report
	 */
	private function runMonitor(OpenSSLCertificate $served, CliArgs $cliArgs): array
	{
		$this->httpClient->setTlsCertificate($served);
		$dnsResolver = new DnsResolverMock();
		$dnsResolver->setRecords(self::NAME, [new DnsRecord(self::NAME, 'IN', 300, 'A', self::ADDRESS)]);
		$monitor = new CertificateMonitor(new CertificateGatherer($this->certificateFactory, $this->httpClient, $dnsResolver), $this->certificatesApiClient, new ConsoleColor(), $cliArgs);
		ob_start();
		$code = $monitor->run();
		$output = ob_get_clean();
		assert(is_string($output));
		return [$code, $output];
	}


	/**
	 * What a server would present: a certificate for the name, valid from now for the given number of days
	 */
	private function issue(int $days): OpenSSLCertificate
	{
		return SelfSignedCertificate::issue(self::NAME, $days);
	}


	private function notAfter(OpenSSLCertificate $certificate): DateTimeImmutable
	{
		return new DateTimeImmutable('@' . OpenSsl::x509parse($certificate)->getValidToTimeT());
	}

}

TestCaseRunner::run(CertificateMonitorTest::class);
