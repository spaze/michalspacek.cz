<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use DateTimeImmutable;
use MichalSpacekCz\DateTime\DateTime;
use Nette\Utils\Json;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class CertificateFactoryTest extends TestCase
{

	public function __construct(
		private readonly CertificateFactory $certificateFactory,
	) {
	}


	public function testGet(): void
	{
		$expected = new Certificate(
			'cn',
			'cn-ext',
			new DateTimeImmutable('-2 weeks'),
			new DateTimeImmutable('+3 weeks'),
			3,
			'CafeCe37',
		);
		/** @var array{commonName:string, commonNameExt:string|null, notBefore:string, notBeforeTz:string, notAfter:string, notAfterTz:string, expiringThreshold:int, serialNumber:string|null, now:string, nowTz:string} $array */
		$array = Json::decode(Json::encode($expected), forceArrays: true);
		$certificate = $this->certificateFactory->get(
			$array['commonName'],
			$array['commonNameExt'],
			$array['notBefore'],
			$array['notBeforeTz'],
			$array['notAfter'],
			$array['notAfterTz'],
			$array['expiringThreshold'],
			$array['serialNumber'],
			$array['now'],
			$array['nowTz'],
		);
		Assert::equal($expected, $certificate);
	}


	public function testListFromLogRequest(): void
	{
		$request = [
			['cn' => 'foo.example', 'ext' => 'ec', 'start' => '1685103252', 'expiry' => '1687695238'],
			['cn' => 'foo.example', 'ext' => '', 'start' => '1685103252', 'expiry' => '1687695238'],
			['cn' => 'foo.example', 'ext' => null, 'start' => '1685103252', 'expiry' => '1687695238'],
			['cn' => 'foo.example', 'start' => '1685103252', 'expiry' => '1687695238'],
			['cn' => 'foo.example', 'ext' => 'ec'],
			['cn' => 'foo.example'],
			['cn' => 'foo.example', 'ext' => null, 'start' => 'monday', 'expiry' => 'friday'],
		];
		$certs = $this->certificateFactory->listFromLogRequest($request);
		Assert::count(2, $certs);
		Assert::type(Certificate::class, $certs[0]);
		Assert::type(Certificate::class, $certs[1]);
		Assert::same('foo.example', $certs[0]->getCommonName());
		Assert::same('ec', $certs[0]->getCommonNameExt());
		Assert::same('foo.example', $certs[1]->getCommonName());
		Assert::null($certs[1]->getCommonNameExt());
		Assert::same('2023-05-26T12:14:12.000000+00:00', $certs[0]->getNotBefore()->format(DateTime::DATE_RFC3339_MICROSECONDS));
		Assert::same('2023-06-25T12:13:58.000000+00:00', $certs[0]->getNotAfter()->format(DateTime::DATE_RFC3339_MICROSECONDS));
	}

}

$runner->run(CertificateFactoryTest::class);
