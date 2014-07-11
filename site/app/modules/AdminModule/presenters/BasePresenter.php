<?php
namespace AdminModule;

/**
 * Base class for all admin module presenters.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
abstract class BasePresenter extends \Nette\Application\UI\Presenter
{


	protected function startup()
	{
		parent::startup();
		$authenticator = $this->getContext()->getByType(\MichalSpacekCz\UserManager::class);
		if (!$this->user->isLoggedIn()) {
			$authenticator->verifySignInAuthorization($this->getSession('admin')->knockKnock);
			$this->redirect('Sign:in');
		}
	}


	public function beforeRender()
	{
		$this->template->trackingCode = false;
	}


	protected function createTemplate($class = null)
	{
		$helpers = $this->getContext()->getByType(\MichalSpacekCz\Templating\Helpers::class);

		$template = parent::createTemplate($class);
		$template->getLatte()->addFilter(null, [new \Bare\Next\Templating\Helpers(), 'loader']);
		$template->getLatte()->addFilter(null, [$helpers, 'loader']);
		return $template;
	}


}
