<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Controls;

use Contributte\Translation\Translator;
use MichalSpacekCz\User\WebAuthn\Authentication\Reauthentication;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyException;
use MichalSpacekCz\User\WebAuthn\WebAuthnAuthenticator;
use Nette\Application\UI\Form;
use Nette\Http\IRequest;
use Tracy\Debugger;

/**
 * Wires a form for a passkey authentication (`navigator.credentials.get`) ceremony: passkey-authenticate.js
 * reads the embedded options, runs the prompt, fills the credential field, and submits. Used by every
 * session-based passkey ceremony, so they all issue and embed the challenge the same way: sign-in, the
 * reauth page, and in-place form confirmation (the account email).
 *
 * The page must also allow the prompt with the `PublicKeyCredentialsGet` permissions policy, added from
 * the presenter's action/render. It can't be done here: the `Permissions-Policy` header is built on the
 * application's response event, before the template (and this form) renders, so adding it from the form
 * would be too late.
 */
final readonly class PasskeyAuthenticationControls
{

	public function __construct(
		private WebAuthnAuthenticator $passkeyAuthenticator,
		private Reauthentication $reauthentication,
		private Translator $translator,
		private IRequest $httpRequest,
	) {
	}


	/**
	 * Adds the credential field, and when the form is rendered, the authentication options the prompt needs.
	 * Options are minted at render rather than at form build because the form is also built while processing
	 * the submit, and regenerating the challenge then would replace the one the passkey just signed. The
	 * caller adds its own submit handling (sign-in logs in; the reauth flows use addReauthTo()).
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


	/**
	 * Makes a form confirm the user's identity with a passkey before it's accepted: adds the ceremony
	 * controls and verifies the assertion during validation, blocking the submit if it isn't the current
	 * user's passkey. Use for the reauth page and for forms that change something sensitive (the account
	 * email). To confirm before merely *viewing* a sensitive page, gate the action with
	 * Admin\BasePresenter::requireReauthentication() instead.
	 */
	public function addReauthTo(Form $form): void
	{
		$this->addOptionsTo($form);
		$form->onValidate[] = function (Form $form): void {
			$values = $form->getUntrustedValues();
			assert(is_string($values->credential));
			try {
				$this->reauthentication->verify($values->credential);
				Debugger::log("Successful passkey reauthentication ({$this->httpRequest->getRemoteAddress()})", 'auth');
			} catch (PasskeyException $e) {
				Debugger::log("Failed passkey reauthentication: {$e->getMessage()} ({$this->httpRequest->getRemoteAddress()})", 'auth');
				$form->addError($this->translator->translate('messages.reauth.failed'));
			}
		};
	}

}
