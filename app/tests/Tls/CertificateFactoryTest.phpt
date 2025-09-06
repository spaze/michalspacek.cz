<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use DateTime;
use DateTimeImmutable;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Database\Row;
use Nette\Utils\Json;
use OpenSSLCertificate;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class CertificateFactoryTest extends TestCase
{

	public function __construct(
		private readonly CertificateFactory $certificateFactory,
	) {
	}


	public function testGet(): void
	{
		$expected = new Certificate(
			'certificate_name',
			'certificate_name-ext',
			null,
			['cert.example', 'www.cert.example'],
			new DateTimeImmutable('-2 weeks'),
			new DateTimeImmutable('+3 weeks'),
			3,
			'CafeCe37',
			new DateTimeImmutable(),
		);
		/** @var array{certificateName:string, certificateNameExt:string|null, cn:string|null, san:list<string>|null, notBefore:string, notBeforeTz:string, notAfter:string, notAfterTz:string, expiringThreshold:int, serialNumber:string|null, now:string, nowTz:string} $array */
		$array = Json::decode(Json::encode($expected), forceArrays: true);
		$certificate = $this->certificateFactory->get(
			$array['certificateName'],
			$array['certificateNameExt'],
			$array['cn'],
			$array['san'],
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


	public function testFromString(): void
	{
		$string = file_get_contents(__DIR__ . '/certificate-no-cn.pem');
		assert(is_string($string));
		$certificateName = 'no-common-name, 🍺';
		$certificate = $this->certificateFactory->fromString($certificateName, $string);
		Assert::same($certificateName, $certificate->getCertificateName());
		Assert::same('06A43647CC3124AC82F42FA8957F5D9972B6', $certificate->getSerialNumber());
		Assert::equal(new DateTimeImmutable('2025-08-23 22:19:36 +00:00'), $certificate->getNotBefore());
		Assert::equal(new DateTimeImmutable('2025-11-21 22:19:35 +00:00'), $certificate->getNotAfter());
	}


	public function testFromObject(): void
	{
		$string = file_get_contents(__DIR__ . '/certificate-no-cn.pem');
		assert(is_string($string));
		$object = openssl_x509_read($string);
		assert($object instanceof OpenSSLCertificate);
		$certificateName = 'no-common-name, 🍺';
		$certificate = $this->certificateFactory->fromObject($certificateName, $object);
		Assert::same($certificateName, $certificate->getCertificateName());
		Assert::same('06A43647CC3124AC82F42FA8957F5D9972B6', $certificate->getSerialNumber());
		Assert::equal(new DateTimeImmutable('2025-08-23 22:19:36 +00:00'), $certificate->getNotBefore());
		Assert::equal(new DateTimeImmutable('2025-11-21 22:19:35 +00:00'), $certificate->getNotAfter());
	}


	public function testFromDatabaseRow(): void
	{
		$row = new Row();
		$row->certificateName = 'foo.example';
		$row->certificateNameExt = 'ec';
		$row->cn = 'cn.example';
		$row->san = '["foo.example","www.foo.example"]';
		$row->notBefore = new DateTime('2020-10-05 04:03:02');
		$row->notBeforeTimezone = 'UTC';
		$row->notAfter = new DateTime('2021-11-06 14:13:12');
		$row->notAfterTimezone = 'Europe/Prague';

		$certificate = $this->certificateFactory->fromDatabaseRow($row);
		Assert::same('foo.example', $certificate->getCertificateName());
		Assert::same('cn.example', $certificate->getCommonName());
		Assert::same(['foo.example', 'www.foo.example'], $certificate->getSubjectAlternativeNames());
		Assert::same('ec', $certificate->getCertificateNameExtension());
		Assert::same(1601870582, $certificate->getNotBefore()->getTimestamp());
		Assert::same('UTC', $certificate->getNotBefore()->getTimezone()->getName());
		Assert::same(1636204392, $certificate->getNotAfter()->getTimestamp());
		Assert::same('Europe/Prague', $certificate->getNotAfter()->getTimezone()->getName());

		$row->cn = null;
		$row->san = '[]';
		$certificate = $this->certificateFactory->fromDatabaseRow($row);
		Assert::null($certificate->getCommonName());
		Assert::same([], $certificate->getSubjectAlternativeNames());

		$row->san = null;
		$certificate = $this->certificateFactory->fromDatabaseRow($row);
		Assert::null($certificate->getSubjectAlternativeNames());
	}

}

TestCaseRunner::run(CertificateFactoryTest::class);
