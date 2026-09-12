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
		$a241 = str_repeat('a', 241); // 241 + strlen('.example.com') is exactly the 253 characters a hostname can have
		return [
			['localhost', null, "There's no security.txt on your machine (◔_◔)"],
			['127.0.0.1', null, "There's no security.txt on your machine (◔_◔)"],
			['127.3.13.37', null, "There's no security.txt on your machine (◔_◔)"],
			['127.0.0.example.com', null, "There's no security.txt on your machine (◔_◔)"],
			['128.0.0.1', '128.0.0.1', null],
			['[::1]', null, "There's no security.txt on your machine (◔_◔)"],
			['[::2]', '[::2]', null],
			["{$a241}.example.com", "{$a241}.example.com", null],
			["{$a241}a.example.com", null, 'The hostname is too long, way too long /┆\\'],
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


	/**
	 * @return list<array{0:string}>
	 */
	public function getUnfetchableSchemes(): array
	{
		return [
			['foo://example.com'],
			[str_repeat('a', 37) . '://example.com'], // longer than the column the scheme is stored in
		];
	}


	/**
	 * The fetcher refuses anything but http and https, but only after the request has been made, which in production
	 * means a Lambda invocation spent to be told no, and an answer filed under a scheme wider than the column holding it.
	 *
	 * @dataProvider getUnfetchableSchemes
	 */
	public function testGetHostRefusesASchemeNothingCanBeFetchedOver(string $url): void
	{
		Assert::exception(function () use ($url): void {
			$this->validatorHost->getHost($url);
		}, SecurityTxtValidatorHostException::class, 'Only https and http can be checked');
	}


	/**
	 * @return list<array{0:string}>
	 */
	public function getRewrittenSchemes(): array
	{
		return [
			['http://example.com'],
			['ftp://example.com'], // a WHATWG special scheme, so the parser can and does ask for https instead
			['ws://example.com'],
		];
	}


	/**
	 * The URL parser asks for https whatever it is handed, and gets it for every scheme WHATWG calls special, which is
	 * why the refusal above catches so little: only a scheme the parser cannot rewrite reaches it.
	 *
	 * @dataProvider getRewrittenSchemes
	 */
	public function testGetHostAcceptsASchemeTheParserCanRewrite(string $url): void
	{
		$validatorUrl = $this->validatorHost->getHost($url);
		Assert::same('example.com', $validatorUrl->getBaseUrl()->getUnicodeHost());
		Assert::same('https', $validatorUrl->getScheme());
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
			Assert::same($expectedHost, $getHost($url)->getBaseUrl()->getUnicodeHost());
		}
	}

}

TestCaseRunner::run(SecurityTxtValidatorHostTest::class);
