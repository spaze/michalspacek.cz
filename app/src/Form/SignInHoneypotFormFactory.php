<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Form\Controls\FormControlsFactory;
use Nette\Http\IRequest;
use Nette\Utils\Html;
use Tracy\Debugger;

readonly class SignInHoneypotFormFactory
{

	public function __construct(
		private UnprotectedFormFactory $factory,
		private FormControlsFactory $controlsFactory,
		private IRequest $httpRequest,
	) {
	}


	public function create(): UiForm
	{
		$form = $this->factory->create();
		$this->controlsFactory->addSignIn($form);
		$form->onSuccess[] = function (UiForm $form): void {
			$values = $form->getFormValues();
			Debugger::log("Sign-in attempt: {$values->username}, {$values->password}, {$this->httpRequest->getRemoteAddress()}", 'honeypot');
			$creds = $values->username . ':' . $values->password;
			if (preg_match('~\slimit\s~i', $creds)) {
				$message = Html::el()
					->setText("No, no, no, no, no, no, no, no, no, no, no, no there's ")
					->addHtml(Html::el('a')
						->href('https://youtu.be/UKmsUAKWclE?t=8')
						->setText('no ')->addHtml(Html::el('code')
							->setText('limit')))
					->addText('!');
			} elseif (stripos($creds, 'honeypot') !== false) {
				$message = 'Jo jo, honeypot, přesně tak';
			} elseif (preg_match('~\sor\s~i', $creds)) {
				$message = 'Dobrej pokusql!';
			} else {
				$message = 'Špatné uživatelské jméno nebo heslo';
			}
			$form->addError($message);
		};
		return $form;
	}

}
