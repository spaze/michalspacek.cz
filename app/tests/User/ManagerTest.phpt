<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDynamicFieldDeclarationInspection */
/** @noinspection PhpUndefinedFieldInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\User;

use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Http\Request;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class ManagerTest extends TestCase
{

	public function __construct(
		private readonly User $user,
		private readonly Database $database,
		private readonly TypedDatabase $typedDatabase,
		private readonly Request $httpRequest,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->user->refreshStorage();
		$this->database->reset();
	}


	public function testGetIdentity(): void
	{
		$id = 1337;
		$username = 'pizza';
		$identity = $this->getManager()->getIdentity($id, $username);
		Assert::type(SimpleIdentity::class, $identity);
		Assert::same($id, $identity->id);
		Assert::same($id, $identity->getId());
		if (!isset($identity->username)) {
			Assert::fail('The username property should be set');
		} else {
			Assert::same($username, $identity->username);
		}
		Assert::same($username, $identity->getData()['username']);
	}


	public function testIsForbiddenMatch(): void
	{
		$this->httpRequest->setRemoteAddress('203.0.113.7');
		$this->database->setFetchFieldDefaultResult(1);
		Assert::true($this->getManager()->isForbidden());
	}


	public function testIsForbiddenNoMatch(): void
	{
		$this->httpRequest->setRemoteAddress('203.0.113.7');
		Assert::false($this->getManager()->isForbidden());
	}


	private function getManager(): Manager
	{
		return new Manager(
			$this->typedDatabase,
			$this->httpRequest,
			'users',
		);
	}

}

TestCaseRunner::run(ManagerTest::class);
