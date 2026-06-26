<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\User;

use MichalSpacekCz\Form\Controls\PasskeyAuthenticationControls;
use MichalSpacekCz\Form\FormFactory;
use MichalSpacekCz\User\WebAuthn\Authentication\ReauthKind;
use Nette\Application\UI\Form;

final readonly class PasskeyReauthenticateFormFactory
{

	public function __construct(
		private FormFactory $factory,
		private PasskeyAuthenticationControls $passkeyAuthenticationControls,
	) {
	}


	/**
	 * @param callable(): void $onSuccess
	 */
	public function create(callable $onSuccess, string $errorUrl, string $canceledUrl): Form
	{
		$form = $this->factory->create();
		$this->passkeyAuthenticationControls->addReauthTo($form, ReauthKind::Interval);
		$form->setHtmlAttribute('data-error-url', $errorUrl);
		$form->setHtmlAttribute('data-canceled-url', $canceledUrl);
		$form->addSubmit('authenticate');
		$form->onSuccess[] = function () use ($onSuccess): void {
			$onSuccess();
		};
		return $form;
	}

}
