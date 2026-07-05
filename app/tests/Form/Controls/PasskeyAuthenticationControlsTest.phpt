<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Controls;

use MichalSpacekCz\Form\FormFactory;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\NullLogger;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Test\User\WebAuthn\PasskeyAuthenticatorMock;
use MichalSpacekCz\User\SecurityActivity\SecurityActivity;
use MichalSpacekCz\User\SecurityActivity\SecurityEventType;
use MichalSpacekCz\User\WebAuthn\Authentication\Exceptions\PasskeyAuthenticationUnknownCredentialException;
use MichalSpacekCz\User\WebAuthn\Authentication\PasskeyAuthenticationResult;
use MichalSpacekCz\User\WebAuthn\Authentication\ReauthKind;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\HiddenField;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Nette\Utils\Arrays;
use Nette\Utils\DateTime;
use Nette\Utils\Json;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class PasskeyAuthenticationControlsTest extends TestCase
{

	private const string INSERT = 'INSERT INTO security_events';


	public function __construct(
		private readonly FormFactory $formFactory,
		private readonly PasskeyAuthenticationControls $controls,
		private readonly ApplicationPresenter $applicationPresenter,
		private readonly PasskeyAuthenticatorMock $passkeyAuthenticator,
		private readonly User $user,
		private readonly Database $database,
		private readonly SecurityActivity $securityActivity,
		private readonly NullLogger $logger,
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
		$this->user->logout();
		$this->passkeyAuthenticator->wontThrow();
		$this->logger->reset();
	}


	public function testIntervalSuccessRecordsTheWindowLength(): void
	{
		$this->passkeyAuthenticator->setAuthenticationResult(new PasskeyAuthenticationResult(42, 'foo', 'cred-id', 'My Passkey'));
		$form = $this->createForm(ReauthKind::Interval);

		Arrays::invoke($form->onValidate, $form);

		$params = $this->database->getParamsArrayForQuery(self::INSERT);
		Assert::same('reauth.interval.success', $params[0]['action']);
		$encrypted = $params[0]['details'];
		assert(is_string($encrypted)); // a window was opened, so details holds the (encrypted) length
		Assert::same(['passkey' => 'My Passkey', 'interval' => '5 minutes'], $this->decodeDetails($encrypted));
	}


	public function testInlineSuccessRecordsTheWindowLength(): void
	{
		$this->passkeyAuthenticator->setAuthenticationResult(new PasskeyAuthenticationResult(42, 'foo', 'cred-id', 'My Passkey'));
		$form = $this->createForm(ReauthKind::Inline);

		Arrays::invoke($form->onValidate, $form);

		$params = $this->database->getParamsArrayForQuery(self::INSERT);
		Assert::same('reauth.inline.success', $params[0]['action']);
		$encrypted = $params[0]['details'];
		assert(is_string($encrypted));
		Assert::same(['passkey' => 'My Passkey', 'interval' => '5 minutes'], $this->decodeDetails($encrypted));
	}


	public function testIntervalFailureRecordsNoWindow(): void
	{
		$this->passkeyAuthenticator->willThrow(new PasskeyAuthenticationUnknownCredentialException('foo'));
		$form = $this->createForm(ReauthKind::Interval);

		Arrays::invoke($form->onValidate, $form);

		$params = $this->database->getParamsArrayForQuery(self::INSERT);
		Assert::same('reauth.interval.failure', $params[0]['action']);
		Assert::null($params[0]['details']); // no window opened, so nothing to record
	}


	public function testInlineFailureRecordsNoWindow(): void
	{
		$this->passkeyAuthenticator->willThrow(new PasskeyAuthenticationUnknownCredentialException('foo'));
		$form = $this->createForm(ReauthKind::Inline);

		Arrays::invoke($form->onValidate, $form);

		$params = $this->database->getParamsArrayForQuery(self::INSERT);
		Assert::same('reauth.inline.failure', $params[0]['action']);
		Assert::null($params[0]['details']);
	}


	public function testInlineRecordsTheGuardedOperation(): void
	{
		$this->passkeyAuthenticator->setAuthenticationResult(new PasskeyAuthenticationResult(42, 'foo', 'cred-id', 'My Passkey'));
		$form = $this->createForm(ReauthKind::Inline, SecurityEventType::NotificationEmailChanged);

		Arrays::invoke($form->onValidate, $form);

		$encrypted = $this->database->getParamsArrayForQuery(self::INSERT)[0]['details'];
		assert(is_string($encrypted));
		Assert::same(['operation' => 'notification.email.changed', 'passkey' => 'My Passkey', 'interval' => '5 minutes'], $this->decodeDetails($encrypted));
	}


	public function testFailedReauthStillNamesTheGuardedOperation(): void
	{
		$this->passkeyAuthenticator->willThrow(new PasskeyAuthenticationUnknownCredentialException('foo'));
		$form = $this->createForm(ReauthKind::Inline, SecurityEventType::NotificationEmailChanged);

		Arrays::invoke($form->onValidate, $form);

		$params = $this->database->getParamsArrayForQuery(self::INSERT);
		Assert::same('reauth.inline.failure', $params[0]['action']);
		$encrypted = $params[0]['details'];
		assert(is_string($encrypted));
		Assert::same(['operation' => 'notification.email.changed'], $this->decodeDetails($encrypted));
	}


	public function testUserMismatchRecordsFailureAndIsLoggedForTheOperator(): void
	{
		// a valid assertion, but it resolves to a different account than the signed-in one (42)
		$this->passkeyAuthenticator->setAuthenticationResult(new PasskeyAuthenticationResult(99, 'foo', 'cred-id', 'My Passkey'));
		$form = $this->createForm(ReauthKind::Inline);

		Arrays::invoke($form->onValidate, $form);

		Assert::same('reauth.inline.failure', $this->database->getParamsArrayForQuery(self::INSERT)[0]['action']);
		Assert::count(1, $this->logger->getLogged()); // a valid passkey resolving to another account is an anomaly the operator should see, unlike an ordinary wrong/unknown passkey
	}


	private function createForm(ReauthKind $kind, ?SecurityEventType $operation = null): Form
	{
		$form = $this->formFactory->create();
		$this->controls->addReauthTo($form, $kind, $operation);
		$this->applicationPresenter->anchorForm($form);
		$field = $form->getComponent('credential');
		assert($field instanceof HiddenField);
		$field->setDefaultValue(Json::encode(['id' => 'test', 'type' => 'public-key']));
		return $form;
	}


	/**
	 * @return array<string, string|null>
	 */
	private function decodeDetails(string $encrypted): array
	{
		// Read the stored ciphertext back through the reader, which owns decryption.
		$this->database->setFetchAllDefaultResult([[
			'action' => 'reauth.interval.success',
			'created' => DateTime::from('2026-06-25 10:00:00'),
			'createdTimezone' => 'UTC',
			'ip' => null,
			'userAgent' => null,
			'details' => $encrypted,
		]]);
		return $this->securityActivity->getEventsForCurrentUser()[0]->details;
	}

}

TestCaseRunner::run(PasskeyAuthenticationControlsTest::class);
