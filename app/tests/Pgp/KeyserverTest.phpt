<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Pgp;

use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class KeyserverTest extends TestCase
{

	public function __construct(
		private readonly Keyserver $keyserver,
	) {
	}


	/**
	 * @return list<array{0:string, 1:string}>
	 */
	public function getKeyNames(): array
	{
		return [
			['B64BDD6E464AB529', 'B64BDD6E464AB529'], // a long key id
			['464AB529', '464AB529'], // a short one
			['3C0AE4D0B4E2D0D5B64BDD6E464AB529AAAAAAAA', '3C0AE4D0B4E2D0D5B64BDD6E464AB529AAAAAAAA'], // a fingerprint
			['0xB64BDD6E464AB529', 'B64BDD6E464AB529'], // already prefixed, which is how key ids are usually written
			['0XB64BDD6E464AB529', 'B64BDD6E464AB529'],
			['0x464AB529', '464AB529'],
		];
	}


	/**
	 * A key gets named three ways, and written with or without the `0x` a keyserver wants, so the prefix is added
	 * exactly once however it arrives: `0x0x...` looks up nothing and would do it quietly.
	 *
	 * @dataProvider getKeyNames
	 */
	public function testAKeyIsLookedUpByWhicheverNameTheCallerHas(string $keyName, string $expectedSearch): void
	{
		Assert::same(
			"https://keyserver.ubuntu.com/pks/lookup?search=0x{$expectedSearch}&op=index",
			$this->keyserver->getLookupUrl($keyName),
		);
	}

}

TestCaseRunner::run(KeyserverTest::class);
