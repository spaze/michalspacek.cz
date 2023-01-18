<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use Contributte\Translation\Translator;
use MichalSpacekCz\Application\LocaleLinkGeneratorInterface;
use MichalSpacekCz\Application\Theme;
use MichalSpacekCz\User\Manager;
use Nette\Application\UI\InvalidLinkException;
use Nette\Application\UI\Presenter;
use Nette\Bridges\ApplicationLatte\DefaultTemplate;
use Nette\Http\IResponse;

/**
 * @property-read DefaultTemplate $template
 */
abstract class BasePresenter extends Presenter
{

	/** @inject */
	public Translator $translator;

	private Manager $authenticator;

	private LocaleLinkGeneratorInterface $localeLinkGenerator;

	private Theme $theme;

	private IResponse $httpResponse;


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
	 * @param LocaleLinkGeneratorInterface $localeLinkGenerator
	 */
	public function injectLocaleLinkGenerator(LocaleLinkGeneratorInterface $localeLinkGenerator): void
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
	 * @param IResponse $httpResponse
	 */
	public function injectHttpResponse(IResponse $httpResponse): void
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


	/** @inheritDoc */
	public function lastModified($lastModified, string $etag = null, string $expire = null): void
	{
		$compression = ini_get('zlib.output_compression');
		ini_set('zlib.output_compression', false);
		parent::lastModified($lastModified, $etag, $expire);
		// If the response was HTTP 304 then the following line won't be reached and 304s won't be compressed
		ini_set('zlib.output_compression', $compression);
	}

}
