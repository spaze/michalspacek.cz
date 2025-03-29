<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Utils;

use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Utils\Exceptions\Base64InvalidInputToDecodeException;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class Base64Test extends TestCase
{

	private const string THIS_STRING_WILL_HAVE_A_PLUS_AND_A_SLASH_WHEN_BASE64_ENCODED = "\xFA\xFA\xFF\xF0";
	private const string THAT_STRING_WILL_BE_ENCODED_TO_BASE64URL_LIKE_THIS = '-vr_8A';


	public function testUrlEncode(): void
	{
		Assert::same(self::THAT_STRING_WILL_BE_ENCODED_TO_BASE64URL_LIKE_THIS, Base64::urlEncode(self::THIS_STRING_WILL_HAVE_A_PLUS_AND_A_SLASH_WHEN_BASE64_ENCODED));
	}


	public function testUrlDecode(): void
	{
		Assert::same(self::THIS_STRING_WILL_HAVE_A_PLUS_AND_A_SLASH_WHEN_BASE64_ENCODED, Base64::urlDecode(self::THAT_STRING_WILL_BE_ENCODED_TO_BASE64URL_LIKE_THIS));
		Assert::exception(function (): void {
			Assert::false(Base64::urlDecode('waldo'));
		}, Base64InvalidInputToDecodeException::class, "Input contains character from outside the Base64 alphabet: 'waldo'");
	}

}

TestCaseRunner::run(Base64Test::class);
