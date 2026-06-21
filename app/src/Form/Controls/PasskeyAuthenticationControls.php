<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Controls;

use MichalSpacekCz\User\WebAuthn\WebAuthnAuthenticator;
use Nette\Application\UI\Form;

/**
 * Wires a form for a passkey authentication (`navigator.credentials.get`) ceremony: passkey-authenticate.js
 * reads the embedded options, runs the prompt, fills the credential field, and submits. Used so every
 * session-based passkey ceremony issues and embeds the challenge the same way (the sign-in form for now).
 *
 * The page must also allow the prompt with the `PublicKeyCredentialsGet` permissions policy, added from the
 * presenter's action/render rather than here: the `Permissions-Policy` header is built on the application's
 * response event, before this form renders, so adding it from the form would be too late.
 */
final readonly class PasskeyAuthenticationControls
{

	public function __construct(
		private WebAuthnAuthenticator $passkeyAuthenticator,
	) {
	}


	/**
	 * Adds the credential field, and when the form is rendered, the authentication options the prompt needs.
	 * Options are minted at render rather than at form build because the form is also built while processing
	 * the submit, and regenerating the challenge then would replace the one the passkey just signed.
	 */
	public function addOptionsTo(Form $form): void
	{
		// Not setRequired(): passkey-authenticate.js fills this field after the ceremony, so it's
		// legitimately empty when the form is validated. verifyAssertion() rejects an empty or invalid
		// value with a caught PasskeyException, so it, not a required rule, is the gate.
		$form->addHidden('credential')
			->setHtmlAttribute('id', 'passkeyCredential');
		$form->onRender[] = function (Form $form): void {
			$form->setHtmlAttribute('data-options', $this->passkeyAuthenticator->generateAuthenticationOptions());
		};
	}

}
