<?php
declare(strict_types = 1);

namespace App\WwwModule\Presenters;

use MichalSpacekCz\Application\LocaleLinkGenerator;
use MichalSpacekCz\Templating\Helpers;
use MichalSpacekCz\User\Manager;
use Nette\Application\UI\InvalidLinkException;
use Nette\Application\UI\ITemplate;
use Nette\Application\UI\Presenter;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Localization\ITranslator;
use Netxten\Templating\Helpers as NetxtenHelpers;
use Spaze\ContentSecurityPolicy\Config;
use stdClass;

/**
 * @property-read Template|stdClass $template
 */
abstract class BasePresenter extends Presenter
{

	/**
	 * @var ITranslator
	 * @inject
	 */
	public $translator;

	/** @var Manager */
	protected $authenticator;

	/** @var Config */
	private $contentSecurityPolicy;

	/** @var LocaleLinkGenerator */
	private $localeLinkGenerator;

	/** @var Helpers */
	private $templateHelpers;


	/** @internal */
	public function injectAuthenticator(Manager $authenticator)
	{
		$this->authenticator = $authenticator;
	}


	/** @internal */
	public function injectContentSecurityPolicy(Config $contentSecurityPolicy)
	{
		$this->contentSecurityPolicy = $contentSecurityPolicy;
	}


	/** @internal */
	public function injectLocaleLinkGenerator(LocaleLinkGenerator $localeLinkGenerator)
	{
		$this->localeLinkGenerator = $localeLinkGenerator;
	}


	/** @internal */
	public function injectTemplateHelpers(Helpers $templateHelpers)
	{
		$this->templateHelpers = $templateHelpers;
	}


	protected function startup(): void
	{
		parent::startup();
		if ($this->authenticator->isForbidden()) {
			$this->forward('Forbidden:');
		}
	}


	public function beforeRender(): void
	{
		$this->template->setTranslator($this->translator);

		try {
			$this->template->localeLinks = $this->localeLinkGenerator->links($this->getLocaleLinkAction(), $this->getLocaleLinkParams());
		} catch (InvalidLinkException $e) {
			$this->template->localeLinks = $this->getLocaleLinkDefault();
		}
	}


	protected function createTemplate(): ITemplate
	{
		/** @var Template $template */
		$template = parent::createTemplate();
		$template->getLatte()->addFilter(null, [new NetxtenHelpers($this->translator->getDefaultLocale()), 'loader']);
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
