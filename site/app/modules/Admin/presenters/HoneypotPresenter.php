<?php
declare(strict_types = 1);

namespace App\AdminModule\Presenters;

use MichalSpacekCz\Form\SignInHoneypot;
use Nette\Utils\ArrayHash;
use Nette\Utils\Html;
use Tracy\Debugger;

/**
 * Honeypot presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class HoneypotPresenter extends \App\WwwModule\Presenters\BasePresenter
{

	public function actionSignIn(): void
	{
		$this->template->pageTitle = 'Přihlásit se';
	}


	protected function createComponentSignIn(string $formName): SignInHoneypot
	{
		$form = new SignInHoneypot($this, $formName);
		$form->onSuccess[] = [$this, 'submittedSignIn'];
		return $form;
	}


	public function submittedSignIn(SignInHoneypot $form, ArrayHash $values): void
	{
		Debugger::log("Sign-in attempt: {$values->username}, {$values->password}, {$this->getHttpRequest()->getRemoteAddress()}", 'honeypot');
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
	}

}
