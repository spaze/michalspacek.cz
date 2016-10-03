<?php
namespace App\Presenters;

/**
 * Base class for all application presenters.
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
		$this->startupEx();
	}


	protected function startupEx()
	{
		$authenticator = $this->getContext()->getByType(\MichalSpacekCz\User\Manager::class);
		if ($authenticator->isForbidden()) {
			$this->forward('Forbidden:');
		}
	}


	public function beforeRender()
	{
		$webTracking = $this->getContext()->getByType(\MichalSpacekCz\WebTracking::class);
		/** @var \Spaze\ContentSecurityPolicy\Config */
		$contentSecurityPolicy = $this->getContext()->getByType(\Spaze\ContentSecurityPolicy\Config::class);
		if ($this->template->trackingCode = $webTracking->isEnabled()) {
			$contentSecurityPolicy->addSnippet('ga');
		}
		$this->template->setTranslator($this->translator);
	}


	protected function createTemplate($class = null)
	{
		$helpers = $this->getContext()->getByType(\MichalSpacekCz\Templating\Helpers::class);

		$template = parent::createTemplate($class);
		$template->getLatte()->addFilter(null, [new \Netxten\Templating\Helpers(), 'loader']);
		$template->getLatte()->addFilter(null, [$helpers, 'loader']);
		$template->getLatte()->addProvider('nonceGenerator', $this->getContext()->getByType(\Spaze\ContentSecurityPolicy\NonceGeneratorInterface::class));
		return $template;
	}

}
