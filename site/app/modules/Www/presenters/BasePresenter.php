<?php
declare(strict_types = 1);

namespace App\WwwModule\Presenters;

use MichalSpacekCz\Application\LocaleLinkGenerator;
use MichalSpacekCz\Theme;
use MichalSpacekCz\User\Manager;
use Nette\Application\UI\InvalidLinkException;
use Nette\Application\UI\Presenter;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Http\Response;
use Nette\Localization\ITranslator;

/**
 * @property-read Template $template
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

	/** @var LocaleLinkGenerator */
	private $localeLinkGenerator;

	private Theme $theme;

	private Response $httpResponse;


	/**
	 * @internal
	 * @param Manager $authenticator
	 */
	public function injectAuthenticator(Manager $authenticator): void
	{
		$this->authenticator = $authenticator;
	}


	/**
	 * @internal
	 * @param LocaleLinkGenerator $localeLinkGenerator
	 */
	public function injectLocaleLinkGenerator(LocaleLinkGenerator $localeLinkGenerator): void
	{
		$this->localeLinkGenerator = $localeLinkGenerator;
	}


	/**
	 * @internal
	 * @param Theme $theme
	 */
	public function injectTheme(Theme $theme): void
	{
		$this->theme = $theme;
	}


	/**
	 * @internal
	 * @param Response $httpResponse
	 */
	public function injectHttpResponse(Response $httpResponse): void
	{
		$this->httpResponse = $httpResponse;
	}


	protected function startup(): void
	{
		parent::startup();
		$this->httpResponse->addHeader('Vary', 'Cookie');
		if ($this->authenticator->isForbidden()) {
			$this->forward('Forbidden:');
		}
	}


	public function beforeRender(): void
	{
		try {
			$this->template->localeLinks = $this->localeLinkGenerator->links($this->getLocaleLinkAction(), $this->getLocaleLinkParams());
		} catch (InvalidLinkException $e) {
			$this->template->localeLinks = $this->getLocaleLinkDefault();
		}
	}


	/**
	 * The default locale links.
	 *
	 * @return string[]|null
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
	 * @return array<string, array<string, string>>
	 */
	protected function getLocaleLinkParams(): array
	{
		return $this->localeLinkGenerator->defaultParams($this->getParameters());
	}


	public function handleDarkFuture(): void
	{
		$this->theme->setDarkMode();
		$this->httpResponse->setExpiration(null);
		$this->redirectPermanent('this');
	}


	public function handleBrightFuture(): void
	{
		$this->theme->setLightMode();
		$this->httpResponse->setExpiration(null);
		$this->redirectPermanent('this');
	}

}
