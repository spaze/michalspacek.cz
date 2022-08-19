<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Utils;

use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class Base64Test extends TestCase
{

	private const THIS_STRING_WILL_HAVE_A_PLUS_AND_A_SLASH_WHEN_BASE64_ENCODED = "\xFA\xFA\xFF\xF0";
	private const THAT_STRING_WILL_BE_ENCODED_TO_BASE64URL_LIKE_THIS = '-vr_8A';


	public function testUrlEncode(): void
	{
		Assert::same(self::THAT_STRING_WILL_BE_ENCODED_TO_BASE64URL_LIKE_THIS, Base64::urlEncode(self::THIS_STRING_WILL_HAVE_A_PLUS_AND_A_SLASH_WHEN_BASE64_ENCODED));
	}


	public function testUrlDecode(): void
	{
		Assert::same(self::THIS_STRING_WILL_HAVE_A_PLUS_AND_A_SLASH_WHEN_BASE64_ENCODED, Base64::urlDecode(self::THAT_STRING_WILL_BE_ENCODED_TO_BASE64URL_LIKE_THIS));
	}

}

$runner->run(Base64Test::class);
