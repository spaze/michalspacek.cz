<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Controls;

use Contributte\Translation\Translator;
use Nette\Forms\Container;

final readonly class PasskeyFormControls
{

	public function __construct(
		private Translator $translator,
	) {
	}


	public function addNameField(Container $container, ?string $currentName = null): void
	{
		$container->addText('name', $this->translator->translate('messages.passkeys.passkeyName'))
			->setRequired()
			->setMaxLength(200)
			->setDefaultValue($currentName)
			->setHtmlAttribute('placeholder', $this->translator->translate('messages.passkeys.passkeyNameExample'));
	}


	public function addRegistrationFields(Container $container, ?string $loadingText = null): void
	{
		$this->addNameField($container);
		// Not setRequired(): passkey-register.js fills this field after the ceremony, so it's legitimately
		// empty when the form is validated. verifyRegistration() rejects an empty or invalid value with a
		// caught PasskeyException, so it, not a required rule, is the gate.
		$container->addHidden('credential')
			->setHtmlAttribute('id', 'passkeyCredential');
		$submit = $container->addSubmit('register', $this->translator->translate('messages.passkeys.registerPasskey'))
			->setHtmlAttribute('id', 'passkeyRegisterButton');
		if ($loadingText !== null) {
			$submit->setDisabled()
				->setHtmlAttribute('data-loading', $loadingText);
		}
	}

}
