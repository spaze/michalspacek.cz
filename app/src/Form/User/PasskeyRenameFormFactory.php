<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\User;

use Contributte\Translation\Translator;
use MichalSpacekCz\Form\Controls\PasskeyFormControls;
use MichalSpacekCz\Form\FormFactory;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyCredentialNotFoundException;
use MichalSpacekCz\User\WebAuthn\UserPasskeys;
use Nette\Forms\Form;
use Symfony\Component\Uid\Uuid;

final readonly class PasskeyRenameFormFactory
{

	public function __construct(
		private FormFactory $factory,
		private UserPasskeys $userPasskeys,
		private Translator $translator,
		private PasskeyFormControls $passkeyFormControls,
	) {
	}


	/**
	 * @param callable(): void $onSuccess
	 * @param callable(): void $onNotFound
	 */
	public function create(callable $onSuccess, callable $onNotFound, Uuid $id, string $currentName): Form
	{
		$form = $this->factory->create();
		$this->passkeyFormControls->addNameField($form, $currentName);
		$form->addSubmit('rename', $this->translator->translate('messages.passkeys.rename.rename'));
		$form->onSuccess[] = function (Form $form) use ($id, $onSuccess, $onNotFound): void {
			$values = $form->getValues();
			assert(is_string($values->name));
			try {
				$this->userPasskeys->renameCredential($id, $values->name);
				$onSuccess();
			} catch (PasskeyCredentialNotFoundException) {
				$onNotFound();
			}
		};
		return $form;
	}

}
