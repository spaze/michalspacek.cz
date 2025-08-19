<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use MichalSpacekCz\Application\Locale\LocaleLink;
use MichalSpacekCz\Application\Locale\LocaleLinkGenerator;
use MichalSpacekCz\Css\CriticalCss;
use MichalSpacekCz\Css\CriticalCssFactory;
use MichalSpacekCz\EasterEgg\FourOhFourButFound;
use MichalSpacekCz\Form\ThemeFormFactory;
use MichalSpacekCz\Form\UiForm;
use MichalSpacekCz\Templating\DefaultTemplate;
use MichalSpacekCz\User\Manager;
use Nette\Application\Request;
use Nette\Application\UI\InvalidLinkException;
use Nette\Application\UI\Presenter;
use Nette\Http\IResponse;
use Override;

/**
 * @property-read DefaultTemplate $template
 */
abstract class BasePresenter extends Presenter
{

	private Manager $authenticator;

	private LocaleLinkGenerator $localeLinkGenerator;

	private ThemeFormFactory $themeFormFactory;

	private IResponse $httpResponse;

	private CriticalCssFactory $criticalCssFactory;

	private FourOhFourButFound $fourOhFourButFound;


	/**
	 * @internal
	 */
	public function injectAuthenticator(Manager $authenticator): void
	{
		$this->authenticator = $authenticator;
	}


	/**
	 * @internal
	 */
	public function injectLocaleLinkGenerator(LocaleLinkGenerator $localeLinkGenerator): void
	{
		$this->localeLinkGenerator = $localeLinkGenerator;
	}


	/**
	 * @internal
	 */
	public function injectThemeFormFactory(ThemeFormFactory $themeFormFactory): void
	{
		$this->themeFormFactory = $themeFormFactory;
	}


	/**
	 * @internal
	 */
	public function injectHttpResponse(IResponse $httpResponse): void
	{
		$this->httpResponse = $httpResponse;
	}


	/**
	 * @internal
	 */
	public function injectCriticalCssFactory(CriticalCssFactory $criticalCssFactory): void
	{
		$this->criticalCssFactory = $criticalCssFactory;
	}


	/**
	 * @internal
	 */
	public function injectFourOhFourButFound(FourOhFourButFound $fourOhFourButFound): void
	{
		$this->fourOhFourButFound = $fourOhFourButFound;
	}


	#[Override]
	protected function startup(): void
	{
		parent::startup();
		$this->httpResponse->addHeader('Vary', 'Cookie');
		if ($this->authenticator->isForbidden() && $this->getRequest()?->getMethod() !== Request::FORWARD) {
			$this->forward(':Www:Forbidden:', ['message' => 'messages.forbidden.spam']);
		}
		$this->fourOhFourButFound->sendItMaybe($this);
	}


	#[Override]
	public function beforeRender(): void
	{
		try {
			$this->template->localeLinks = $this->localeLinkGenerator->links($this->getLocaleLinksGeneratorDestination(), $this->getLocaleLinksGeneratorParams());
		} catch (InvalidLinkException) {
			$this->template->localeLinks = $this->getLocaleLinkDefault();
		}
	}


	protected function getLocaleLinksGeneratorDestination(): string
	{
		return $this->getLocaleLinkAction();
	}


	/**
	 * @return array<string, array<array-key, mixed>>
	 */
	protected function getLocaleLinksGeneratorParams(): array
	{
		return $this->getLocaleLinkParams();
	}


	/**
	 * The default locale links.
	 *
	 * @return array<string, LocaleLink>
	 */
	protected function getLocaleLinkDefault(): array
	{
		return [];
	}


	/**
	 * Default module:presenter:action for locale links.
	 */
	protected function getLocaleLinkAction(): string
	{
		return $this->getName() . ':' . $this->getAction();
	}


	/**
	 * Default parameters for locale links.
	 *
	 * @return array<string, array<array-key, mixed>>
	 */
	protected function getLocaleLinkParams(): array
	{
		return $this->localeLinkGenerator->defaultParams($this->getParameters());
	}


	protected function createComponentCriticalCss(): CriticalCss
	{
		return $this->criticalCssFactory->create();
	}


	protected function createComponentTheme(): UiForm
	{
		return $this->themeFormFactory->create(function (): void {
			$this->httpResponse->setExpiration(null);
			$this->redirect('this');
		});
	}

}
