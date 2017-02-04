<?php
declare(strict_types = 1);

namespace App\WwwModule\Presenters;

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


	protected function startup(): void
	{
		parent::startup();
		$this->startupEx();
	}


	protected function startupEx(): void
	{
		$authenticator = $this->getContext()->getByType(\MichalSpacekCz\User\Manager::class);
		if ($authenticator->isForbidden()) {
			$this->forward('Forbidden:');
		}
	}


	public function beforeRender(): void
	{
		$webTracking = $this->getContext()->getByType(\MichalSpacekCz\WebTracking::class);
		/** @var \Spaze\ContentSecurityPolicy\Config */
		$contentSecurityPolicy = $this->getContext()->getByType(\Spaze\ContentSecurityPolicy\Config::class);
		if ($this->template->trackingCode = $webTracking->isEnabled()) {
			$contentSecurityPolicy->addSnippet('ga');
		}
		$this->template->setTranslator($this->translator);

		try {
			/** @var \MichalSpacekCz\Application\LocaleLinkGenerator */
			$localeLinkGenerator = $this->getContext()->getByType(\MichalSpacekCz\Application\LocaleLinkGenerator::class);
			$this->template->localeLinks = $localeLinkGenerator->links($this->getName() . ':' . $this->getAction(), $this->params);
		} catch (\Nette\Application\UI\InvalidLinkException $e) {
			$this->template->localeLinks = null;
		}
	}


	protected function createTemplate(): \Nette\Application\UI\ITemplate
	{
		$helpers = $this->getContext()->getByType(\MichalSpacekCz\Templating\Helpers::class);

		$template = parent::createTemplate();
		$template->getLatte()->addFilter(null, [new \Netxten\Templating\Helpers(), 'loader']);
		$template->getLatte()->addFilter(null, [$helpers, 'loader']);
		return $template;
	}

}
