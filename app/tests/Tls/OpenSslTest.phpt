<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Tls\Exceptions\OpenSslException;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class OpenSslTest extends TestCase
{

	public function testX509Parse(): void
	{
		$file = __DIR__ . '/certificate.pem';
		$certificate = @file_get_contents($file); // @ intentionally, converted to a failure
		if ($certificate === false) {
			Assert::fail('Cannot read ' . $file);
		} else {
			$expected = new OpenSslX509ParseResult('michalspacek.cz', 1682947521, 1690723520, '03F3ABC4EB1C13E0D4447CA61298423C0F02');
			Assert::equal($expected, OpenSsl::x509parse($certificate));
		}
	}


	public function testX509ParseError(): void
	{
		Assert::exception(function (): void {
			OpenSsl::x509parse('-----BEGIN ¯\_(ツ)_/¯ END-----');
		}, OpenSslException::class, 'error:04800066:PEM routines::bad end line');
	}

}

TestCaseRunner::run(OpenSslTest::class);
