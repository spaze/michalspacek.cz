<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\User;

use MichalSpacekCz\Http\Session\SessionRevoker;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\User\AuthTokens\AuthTokensRevoker;
use Nette\DI\Container;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class UserAccessRevokerTest extends TestCase
{

	public function __construct(
		private readonly Container $container,
	) {
	}


	public function testCollectionRevokesEveryTokenAndSession(): void
	{
		$classes = [];
		foreach ($this->container->findByType(UserAccessRevoker::class) as $name) {
			$service = $this->container->getService($name);
			assert($service instanceof UserAccessRevoker);
			$classes[] = $service::class;
		}
		sort($classes);

		// A passkey reset revokes every way into the account by iterating this collection, so its membership is
		// the security boundary. AuthTokensRevoker kills all auth tokens (permanent-login, reset, add, future types),
		// SessionRevoker kills sessions. PermanentLogin is deliberately NOT here: AuthTokensRevoker already covers its
		// tokens. If this assertion fails, a revoker was added or dropped -- make sure that change to reset was intended.
		$expected = [AuthTokensRevoker::class, SessionRevoker::class];
		sort($expected);
		Assert::same($expected, $classes);
	}

}

TestCaseRunner::run(UserAccessRevokerTest::class);
