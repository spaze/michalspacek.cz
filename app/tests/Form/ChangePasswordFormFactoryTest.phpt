<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\PrivateProperty;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\DI\Container;
use Nette\Security\Passwords;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Nette\Utils\Arrays;
use Spaze\Encryption\SymmetricKeyEncryption;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class ChangePasswordFormFactoryTest extends TestCase
{

	private const int USER_ID = 1337;
	private const string USERNAME = 'foo';
	private const string PASSWORD = 'bar';
	private const string NEW_PASSWORD = 'baz';

	private readonly SymmetricKeyEncryption $passwordEncryption;

	private ?bool $result = null;


	public function __construct(
		private readonly Database $database,
		private readonly ChangePasswordFormFactory $formFactory,
		private readonly User $user,
		private readonly Passwords $passwords,
		private readonly ApplicationPresenter $applicationPresenter,
		Container $container,
	) {
		$service = $container->getService('passwordEncryption');
		assert($service instanceof SymmetricKeyEncryption);
		$this->passwordEncryption = $service;
	}


	public function testCreateOnSuccessAdd(): void
	{
		PrivateProperty::setValue($this->user, 'identity', new SimpleIdentity(self::USER_ID, [], ['username' => self::USERNAME]));
		PrivateProperty::setValue($this->user, 'authenticated', true);
		$this->database->setFetchDefaultResult([
			'userId' => self::USER_ID,
			'username' => self::USERNAME,
			'password' => $this->passwordEncryption->encrypt($this->passwords->hash(self::PASSWORD)),
		]);
		$form = $this->formFactory->create(
			function (): void {
				$this->result = true;
			},
		);
		$form->setDefaults([
			'password' => self::PASSWORD,
			'newPassword' => self::NEW_PASSWORD,
		]);
		$this->applicationPresenter->anchorForm($form);
		Arrays::invoke($form->onSuccess, $form);
		Assert::true($this->result);
		[$hash, $userId] = $this->database->getParamsForQuery('UPDATE users SET password = ? WHERE id_user = ?');
		assert(is_string($hash));
		assert(is_int($userId));
		Assert::same(self::USER_ID, $userId);
		Assert::true($this->passwords->verify(self::NEW_PASSWORD, $this->passwordEncryption->decrypt($hash)));
	}

}

TestCaseRunner::run(ChangePasswordFormFactoryTest::class);
