<?php
/** @noinspection PhpFullyQualifiedNameUsageInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class OpenSslTest extends TestCase
{

	public function testX509Parse(): void
	{
		Assert::noError(function (): void {
			Assert::type('array', OpenSsl::x509parse(file_get_contents(__DIR__ . '/certificate.pem')));
		});
	}


	/**
	 * @throws \MichalSpacekCz\Tls\Exceptions\OpenSslException error:04800066:PEM routines::bad end line
	 */
	public function testX509ParseError(): void
	{
		OpenSsl::x509parse('-----BEGIN ¯\_(ツ)_/¯ END-----');
	}

}

$runner->run(OpenSslTest::class);
