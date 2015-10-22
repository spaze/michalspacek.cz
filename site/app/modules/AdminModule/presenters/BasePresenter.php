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

	/**
	 * @var \Nette\Localization\ITranslator
	 * @inject
	 */
	public $translator;


	protected function startup()
	{
		parent::startup();

		$authenticator = $this->getContext()->getByType(\MichalSpacekCz\User\Manager::class);
		if (!$this->user->isLoggedIn()) {
			$this->redirect($authenticator->isReturningUser() ? 'Sign:in' : 'Honeypot:signIn');
		}
	}


	public function beforeRender()
	{
		$this->template->trackingCode = false;
		$this->template->setTranslator($this->translator);
	}


	protected function createTemplate($class = null)
	{
		$helpers = $this->getContext()->getByType(\MichalSpacekCz\Templating\Helpers::class);

		$template = parent::createTemplate($class);
		$template->getLatte()->addFilter(null, [new \Netxten\Templating\Helpers(), 'loader']);
		$template->getLatte()->addFilter(null, [$helpers, 'loader']);
		return $template;
	}

}
