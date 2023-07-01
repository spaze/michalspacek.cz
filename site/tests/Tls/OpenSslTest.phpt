<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use MichalSpacekCz\Tls\Exceptions\OpenSslException;
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


	public function testX509ParseError(): void
	{
		Assert::exception(function (): void {
			OpenSsl::x509parse('-----BEGIN ¯\_(ツ)_/¯ END-----');
		}, OpenSslException::class, 'error:04800066:PEM routines::bad end line');
	}

}

$runner->run(OpenSslTest::class);
