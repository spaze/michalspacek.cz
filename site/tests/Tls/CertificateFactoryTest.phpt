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


	public function testFromArray(): void
	{
		$certificate = new Certificate(
			'cn',
			'cn-ext',
			new DateTimeImmutable('-2 weeks'),
			new DateTimeImmutable('+3 weeks'),
			3,
			'CafeCe37',
		);
		$array = Json::decode(Json::encode($certificate), Json::FORCE_ARRAY);
		Assert::equal($certificate, $this->certificateFactory->fromArray($array));
	}

}

$runner->run(CertificateFactoryTest::class);
