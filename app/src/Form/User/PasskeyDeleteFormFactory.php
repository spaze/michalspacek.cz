<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\User;

use Contributte\Translation\Translator;
use MichalSpacekCz\Form\FormFactory;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyCredentialNotFoundException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyCredentialSignedInWithException;
use MichalSpacekCz\User\WebAuthn\Passkeys;
use Nette\Forms\Form;
use Symfony\Component\Uid\Uuid;

final readonly class PasskeyDeleteFormFactory
{

	public function __construct(
		private FormFactory $factory,
		private Passkeys $passkeys,
		private Translator $translator,
	) {
	}


	/**
	 * @param callable(): void $onSuccess
	 * @param callable(): void $onSignedInWith
	 * @param callable(): void $onNotFound
	 */
	public function create(callable $onSuccess, callable $onSignedInWith, callable $onNotFound, Uuid $id): Form
	{
		$form = $this->factory->create();
		$form->addSubmit('delete', $this->translator->translate('messages.passkeys.delete.delete'));
		$form->onSuccess[] = function () use ($id, $onSuccess, $onSignedInWith, $onNotFound): void {
			try {
				$this->passkeys->deleteCredential($id);
				$onSuccess();
			} catch (PasskeyCredentialSignedInWithException) {
				$onSignedInWith();
			} catch (PasskeyCredentialNotFoundException) {
				$onNotFound();
			}
		};
		return $form;
	}

}
