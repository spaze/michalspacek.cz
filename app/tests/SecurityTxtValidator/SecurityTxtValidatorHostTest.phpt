<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator;

use MichalSpacekCz\SecurityTxtValidator\Exceptions\SecurityTxtValidatorHostException;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class SecurityTxtValidatorHostTest extends TestCase
{

	public function __construct(
		private readonly SecurityTxtValidatorHost $validatorHost,
	) {
	}


	/**
	 * @return list<array{0:string, 1:string|null, 2:string|null}>
	 */
	public function getErrorMessages(): array
	{
		$a243 = str_repeat('a', 243); // 243 + strlen('.example.com') is exactly the 255 the column holds
		return [
			['localhost', null, "There's no security.txt on your machine (◔_◔)"],
			['127.0.0.1', null, "There's no security.txt on your machine (◔_◔)"],
			['127.3.13.37', null, "There's no security.txt on your machine (◔_◔)"],
			['127.0.0.example.com', null, "There's no security.txt on your machine (◔_◔)"],
			['128.0.0.1', '128.0.0.1', null],
			['[::1]', null, "There's no security.txt on your machine (◔_◔)"],
			['[::2]', '[::2]', null],
			["{$a243}.example.com", "{$a243}.example.com", null],
			["{$a243}a.example.com", null, 'The hostname is too long, way too long /┆\\'],
			['example.com', 'example.com', null],
			// To confirm normalization in spaze/security-txt:
			['LocalHost', null, "There's no security.txt on your machine (◔_◔)"],
			['eXaMpLe.com', 'example.com', null],
		];
	}


	/**
	 * @dataProvider getErrorMessages
	 */
	public function testGetHost(string $host, ?string $expectedHost, ?string $errorMessage): void
	{
		$this->assertHost($host, $expectedHost, $errorMessage);
		$this->assertHost("{$host}/foo", $expectedHost, $errorMessage);
		$this->assertHost("https://{$host}", $expectedHost, $errorMessage);
		$this->assertHost("https://{$host}/foo", $expectedHost, $errorMessage);
	}


	public function testGetHostInvalidUrl(): void
	{
		$this->assertHost('//', null, 'Invalid URL or hostname');
	}


	private function assertHost(string $url, ?string $expectedHost, ?string $errorMessage): void
	{
		$getHost = $this->validatorHost->getHost(...);
		if ($errorMessage !== null) {
			Assert::exception(function () use ($getHost, $url): void {
				$getHost($url);
			}, SecurityTxtValidatorHostException::class, $errorMessage);
		} else {
			Assert::same($expectedHost, $getHost($url)->getUrl()->getUnicodeHost());
		}
	}

}

TestCaseRunner::run(SecurityTxtValidatorHostTest::class);
