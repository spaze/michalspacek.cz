<?php
declare(strict_types = 1);

namespace App\WwwModule\Presenters;

/**
 * Base class for all application presenters.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 *
 * @property-read \Nette\Bridges\ApplicationLatte\Template|\stdClass $template
 */
abstract class BasePresenter extends \Nette\Application\UI\Presenter
{

	/**
	 * @var \Nette\Localization\ITranslator
	 * @inject
	 */
	public $translator;

	/** @var \MichalSpacekCz\User\Manager */
	private $authenticator;

	/** @var \MichalSpacekCz\WebTracking */
	private $webTracking;

	/** @var \Spaze\ContentSecurityPolicy\Config */
	private $contentSecurityPolicy;

	/** @var \MichalSpacekCz\Application\LocaleLinkGenerator */
	private $localeLinkGenerator;

	/** @var \MichalSpacekCz\Templating\Helpers */
	private $templateHelpers;


	/**
	 * @internal
	 * @param \MichalSpacekCz\User\Manager $authenticator
	 */
	public function injectAuthenticator(\MichalSpacekCz\User\Manager $authenticator)
	{
		$this->authenticator = $authenticator;
	}


	/**
	 * @internal
	 * @param \MichalSpacekCz\WebTracking $webTracking
	 */
	public function injectWebTracking(\MichalSpacekCz\WebTracking $webTracking)
	{
		$this->webTracking = $webTracking;
	}


	/**
	 * @internal
	 * @param \Spaze\ContentSecurityPolicy\Config $contentSecurityPolicy
	 */
	public function injectContentSecurityPolicy(\Spaze\ContentSecurityPolicy\Config $contentSecurityPolicy)
	{
		$this->contentSecurityPolicy = $contentSecurityPolicy;
	}


	/**
	 * @internal
	 * @param \MichalSpacekCz\Application\LocaleLinkGenerator $localeLinkGenerator
	 */
	public function injectLocaleLinkGenerator(\MichalSpacekCz\Application\LocaleLinkGenerator $localeLinkGenerator)
	{
		$this->localeLinkGenerator = $localeLinkGenerator;
	}


	/**
	 * @internal
	 * @param \MichalSpacekCz\Templating\Helpers $templateHelpers
	 */
	public function injectTemplateHelpers(\MichalSpacekCz\Templating\Helpers $templateHelpers)
	{
		$this->templateHelpers = $templateHelpers;
	}


	protected function startup(): void
	{
		parent::startup();
		$this->startupEx();
	}


	protected function startupEx(): void
	{
		if ($this->authenticator->isForbidden()) {
			$this->forward('Forbidden:');
		}
	}


	public function beforeRender(): void
	{
		if ($this->template->trackingCode = $this->webTracking->isEnabled()) {
			$this->contentSecurityPolicy->addSnippet('ga');
		}
		$this->template->setTranslator($this->translator);

		try {
			$this->template->localeLinks = $this->localeLinkGenerator->links($this->getLocaleLinkAction(), $this->getLocaleLinkParams());
		} catch (\Nette\Application\UI\InvalidLinkException $e) {
			$this->template->localeLinks = $this->getLocaleLinkDefault();
		}
	}


	protected function createTemplate(): \Nette\Application\UI\ITemplate
	{
		$template = parent::createTemplate();
		$template->getLatte()->addFilter(null, [new \Netxten\Templating\Helpers(), 'loader']);
		$template->getLatte()->addFilter(null, [$this->templateHelpers, 'loader']);
		return $template;
	}


	/**
	 * The default locale links.
	 *
	 * @return array|null
	 */
	protected function getLocaleLinkDefault(): ?array
	{
		return null;
	}


	/**
	 * Default module:presenter:action for locale links.
	 *
	 * @return string
	 */
	protected function getLocaleLinkAction(): string
	{
		return $this->getName() . ':' . $this->getAction();
	}


	/**
	 * Default parameters for locale links.
	 *
	 * @return array
	 */
	protected function getLocaleLinkParams(): array
	{
		return $this->localeLinkGenerator->defaultParams($this->getParameters());
	}

}
