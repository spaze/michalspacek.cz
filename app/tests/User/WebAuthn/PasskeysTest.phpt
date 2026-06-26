<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn;

use DateTimeImmutable;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Database\ResultSet;
use MichalSpacekCz\Test\DateTime\DateTimeMachineFactory;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\User\SecurityActivity\SecurityActivity;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyCredentialNotFoundException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyCredentialSignedInWithException;
use MichalSpacekCz\User\WebAuthn\Session\PasskeySessionSection;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Nette\Utils\DateTime;
use Override;
use Symfony\Component\Uid\Uuid;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class PasskeysTest extends TestCase
{

	private const string ID_1 = '019e08b4-8b1e-77b7-bb24-3c8e4aee3444';
	private const string ID_2 = '019e08b4-8b1e-77b7-bb24-3c8e4aee3445';


	public function __construct(
		private readonly Database $database,
		private readonly Passkeys $passkeys,
		private readonly DateTimeMachineFactory $dateTimeMachineFactory,
		private readonly PasskeySessionSection $passkeySessionSection,
		private readonly User $user,
		private readonly SecurityActivity $securityActivity,
	) {
	}


	#[Override]
	protected function setUp(): void
	{
		$this->user->login(new SimpleIdentity(42));
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
		$this->dateTimeMachineFactory->setDateTime(null);
		$this->passkeySessionSection->removeAll();
		$this->user->logout();
	}


	public function testGetPasskeysEmpty(): void
	{
		Assert::same([], $this->passkeys->getPasskeys());
	}


	public function testGetPasskeysSingleNeverUsed(): void
	{
		$created = DateTime::from('2026-05-01 10:00:00');
		$this->database->setFetchAllDefaultResult([[
			'id' => Uuid::fromRfc4122(self::ID_1)->toBinary(),
			'credentialId' => 'cred-id-1',
			'name' => 'Phone',
			'created' => $created,
			'createdTimezone' => 'UTC',
			'lastUsed' => null,
			'lastUsedTimezone' => null,
		]]);
		$result = $this->passkeys->getPasskeys();
		Assert::count(1, $result);
		Assert::same(self::ID_1, $result[0]->getId());
		Assert::same('Phone', $result[0]->getName());
		Assert::null($result[0]->getLastUsedAt());
		Assert::null($result[0]->getLastUsedDaysAgo());
		Assert::false($result[0]->isSignedInWith());
	}


	public function testGetPasskeysMarksSignedInWith(): void
	{
		$now = new DateTimeImmutable('2026-05-08 12:00:00');
		$this->dateTimeMachineFactory->setDateTime($now);
		$created = DateTime::from('2026-05-01 10:00:00');
		$recentlyUsed = DateTime::from('2026-05-07 10:00:00');
		$olderUsed = DateTime::from('2026-05-03 10:00:00');
		$this->database->setFetchAllDefaultResult([
			[
				'id' => Uuid::fromRfc4122(self::ID_1)->toBinary(),
				'credentialId' => 'cred-id-1',
				'name' => 'Laptop',
				'created' => $created,
				'createdTimezone' => 'UTC',
				'lastUsed' => $recentlyUsed,
				'lastUsedTimezone' => 'UTC',
			],
			[
				'id' => Uuid::fromRfc4122(self::ID_2)->toBinary(),
				'credentialId' => 'cred-id-2',
				'name' => 'Phone',
				'created' => $created,
				'createdTimezone' => 'UTC',
				'lastUsed' => $olderUsed,
				'lastUsedTimezone' => 'UTC',
			],
		]);
		$this->passkeySessionSection->setSignedInCredentialId('cred-id-1');
		$result = $this->passkeys->getPasskeys();
		Assert::count(2, $result);
		Assert::same(self::ID_1, $result[0]->getId());
		Assert::true($result[0]->isSignedInWith());
		Assert::same(1, $result[0]->getLastUsedDaysAgo());
		Assert::same(self::ID_2, $result[1]->getId());
		Assert::false($result[1]->isSignedInWith());
		Assert::same(5, $result[1]->getLastUsedDaysAgo());
	}


	public function testGetPasskeysNullSessionCredentialIdNothingMarked(): void
	{
		$created = DateTime::from('2026-05-01 10:00:00');
		$this->database->setFetchAllDefaultResult([
			[
				'id' => Uuid::fromRfc4122(self::ID_1)->toBinary(),
				'credentialId' => 'cred-id-1',
				'name' => 'Phone',
				'created' => $created,
				'createdTimezone' => 'UTC',
				'lastUsed' => null,
				'lastUsedTimezone' => null,
			],
			[
				'id' => Uuid::fromRfc4122(self::ID_2)->toBinary(),
				'credentialId' => 'cred-id-2',
				'name' => 'Laptop',
				'created' => $created,
				'createdTimezone' => 'UTC',
				'lastUsed' => null,
				'lastUsedTimezone' => null,
			],
		]);
		$result = $this->passkeys->getPasskeys();
		Assert::count(2, $result);
		Assert::same(self::ID_1, $result[0]->getId());
		Assert::false($result[0]->isSignedInWith());
		Assert::same(self::ID_2, $result[1]->getId());
		Assert::false($result[1]->isSignedInWith());
	}


	public function testGetCredentialNameById(): void
	{
		$this->database->setFetchFieldDefaultResult('My Phone');
		Assert::same('My Phone', $this->passkeys->getCredentialNameById(Uuid::fromRfc4122(self::ID_1)));
	}


	public function testGetCredentialNameByIdNotFound(): void
	{
		Assert::exception(function (): void {
			$this->passkeys->getCredentialNameById(Uuid::fromRfc4122(self::ID_1));
		}, PasskeyCredentialNotFoundException::class);
	}


	public function testRenameCredential(): void
	{
		$this->database->setResultSet(new ResultSet(1));
		$this->passkeys->renameCredential(Uuid::fromRfc4122(self::ID_1), 'New Name');
		Assert::same(
			['passkeys', 'New Name', Uuid::fromRfc4122(self::ID_1)->toBinary(), 42],
			$this->database->getParamsForQuery('UPDATE ?name SET name = ? WHERE id_passkey = ? AND key_user = ?'),
		);
		Assert::same('passkey.renamed', $this->database->getParamsArrayForQuery('INSERT INTO security_events')[0]['action']);
	}


	public function testRenameCredentialSameName(): void
	{
		$this->database->setResultSet(new ResultSet(0));
		$this->database->addFetchFieldResult(1);
		Assert::noError(function (): void {
			$this->passkeys->renameCredential(Uuid::fromRfc4122(self::ID_1), 'Same Name');
		});
		Assert::same([], $this->database->getParamsArrayForQuery('INSERT INTO security_events')); // renaming to the same name changes no row, so nothing is recorded
	}


	public function testRenameCredentialNotFound(): void
	{
		$this->database->setResultSet(new ResultSet(0));
		Assert::exception(function (): void {
			$this->passkeys->renameCredential(Uuid::fromRfc4122(self::ID_1), 'New Name');
		}, PasskeyCredentialNotFoundException::class);
	}


	public function testDeleteCredential(): void
	{
		$idBinary = Uuid::fromRfc4122(self::ID_1)->toBinary();
		$this->database->setResultSet(new ResultSet(1)); // the delete removed the credential
		$this->database->addFetchFieldResult('uPhone');
		$this->passkeys->deleteCredential(Uuid::fromRfc4122(self::ID_1));
		Assert::same(
			['passkeys', $idBinary, 42, null, null],
			$this->database->getParamsForQuery('DELETE FROM ?name WHERE id_passkey = ? AND key_user = ? AND (? IS NULL OR credential_id != ?)'),
		);
		$event = $this->database->getParamsArrayForQuery('INSERT INTO security_events');
		Assert::same('passkey.deleted', $event[0]['action']);
		$details = $event[0]['details'];
		assert(is_string($details));
		$this->database->setFetchAllDefaultResult([[
			'action' => 'passkey.deleted',
			'created' => DateTime::from('2026-06-26 10:00:00'),
			'createdTimezone' => 'UTC',
			'ip' => null,
			'userAgent' => null,
			'details' => $details,
		]]);
		Assert::same(['name' => 'uPhone'], $this->securityActivity->getEventsForCurrentUser()[0]->details);
	}


	public function testDeleteCredentialNotFound(): void
	{
		$this->database->addFetchFieldResult(null);
		Assert::exception(function (): void {
			$this->passkeys->deleteCredential(Uuid::fromRfc4122(self::ID_1));
		}, PasskeyCredentialNotFoundException::class);
	}


	public function testDeleteCredentialSignedInWith(): void
	{
		$this->passkeySessionSection->setSignedInCredentialId('cred-id-1');
		$this->database->addFetchFieldResult('uPhone'); // the name lookup finds it
		$this->database->addFetchFieldResult(1); // still there after the zero-row delete, so it's the signed-in one
		$this->database->setResultSet(new ResultSet(0));
		Assert::exception(function (): void {
			$this->passkeys->deleteCredential(Uuid::fromRfc4122(self::ID_1));
		}, PasskeyCredentialSignedInWithException::class);
	}


	public function testDeleteCredentialConcurrentlyDeleted(): void
	{
		$this->database->addFetchFieldResult('uPhone'); // found when the name is read
		$this->database->addFetchFieldResult(null); // gone by the time the delete runs
		$this->database->setResultSet(new ResultSet(0));
		Assert::exception(function (): void {
			$this->passkeys->deleteCredential(Uuid::fromRfc4122(self::ID_1));
		}, PasskeyCredentialNotFoundException::class);
	}

}

TestCaseRunner::run(PasskeysTest::class);
