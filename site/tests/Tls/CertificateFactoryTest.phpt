<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use DateTimeImmutable;
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

}

$runner->run(CertificateFactoryTest::class);
