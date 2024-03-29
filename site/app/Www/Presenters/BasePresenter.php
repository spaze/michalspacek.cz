<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use DateTimeInterface;
use MichalSpacekCz\Application\Locale\LocaleLink;
use MichalSpacekCz\Application\Locale\LocaleLinkGenerator;
use MichalSpacekCz\Application\Theme;
use MichalSpacekCz\Css\CriticalCss;
use MichalSpacekCz\Css\CriticalCssFactory;
use MichalSpacekCz\User\Manager;
use Nette\Application\UI\InvalidLinkException;
use Nette\Application\UI\Presenter;
use Nette\Bridges\ApplicationLatte\DefaultTemplate;
use Nette\Http\IResponse;
use Override;

/**
 * @property-read DefaultTemplate $template
 */
abstract class BasePresenter extends Presenter
{

	private Manager $authenticator;

	private LocaleLinkGenerator $localeLinkGenerator;

	private Theme $theme;

	private IResponse $httpResponse;

	private CriticalCssFactory $criticalCssFactory;


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
	public function injectTheme(Theme $theme): void
	{
		$this->theme = $theme;
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


	#[Override]
	protected function startup(): void
	{
		parent::startup();
		$this->httpResponse->addHeader('Vary', 'Cookie');
		if ($this->authenticator->isForbidden()) {
			$this->forward('Forbidden:');
		}
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
	 * @return array<string, array<string, string|null>>
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
	 * @return array<string, array<string, string|null>>
	 */
	protected function getLocaleLinkParams(): array
	{
		return $this->localeLinkGenerator->defaultParams($this->getParameters());
	}


	public function handleDarkFuture(): never
	{
		$this->theme->setDarkMode();
		$this->httpResponse->setExpiration(null);
		$this->redirectPermanent('this');
	}


	public function handleBrightFuture(): never
	{
		$this->theme->setLightMode();
		$this->httpResponse->setExpiration(null);
		$this->redirectPermanent('this');
	}


	#[Override]
	public function lastModified(string|int|DateTimeInterface|null $lastModified, string $etag = null, string $expire = null): void
	{
		$compression = ini_get('zlib.output_compression');
		ini_set('zlib.output_compression', false);
		parent::lastModified($lastModified, $etag, $expire);
		// If the response was HTTP 304 then the following line won't be reached and 304s won't be compressed
		ini_set('zlib.output_compression', $compression);
	}


	protected function createComponentCriticalCss(): CriticalCss
	{
		return $this->criticalCssFactory->create();
	}

}
