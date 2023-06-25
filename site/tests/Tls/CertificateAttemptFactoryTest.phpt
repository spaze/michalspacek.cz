<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class CertificateAttemptFactoryTest extends TestCase
{

	public function __construct(
		private readonly CertificateAttemptFactory $certificateAttemptFactory,
	) {
	}


	public function testListFromLogRequest(): void
	{
		$request = [
			['cn' => 'foo.example', 'ext' => 'ec'],
			['cn' => 'foo.example', 'ext' => ''],
			['cn' => 'foo.example', 'ext' => null],
			['cn' => 'foo.example'],
		];
		$certs = $this->certificateAttemptFactory->listFromLogRequest($request);
		Assert::count(2, $certs);
		Assert::type(CertificateAttempt::class, $certs[0]);
		Assert::type(CertificateAttempt::class, $certs[1]);
		Assert::same('foo.example', $certs[0]->getCommonName());
		Assert::same('ec', $certs[0]->getCommonNameExt());
		Assert::same('foo.example', $certs[1]->getCommonName());
		Assert::null($certs[1]->getCommonNameExt());
	}

}

$runner->run(CertificateAttemptFactoryTest::class);
