<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Www;

use DateTimeInterface;
use MichalSpacekCz\Application\ActionMethodAttributes;
use MichalSpacekCz\Application\Locale\LocaleLink;
use MichalSpacekCz\Application\Locale\LocaleLinkGenerator;
use MichalSpacekCz\Css\CriticalCss;
use MichalSpacekCz\Css\CriticalCssFactory;
use MichalSpacekCz\EasterEgg\FourOhFourButFound\FourOhFourButFound;
use MichalSpacekCz\Form\ThemeFormFactory;
use MichalSpacekCz\Http\SameOrigin\CrossOriginRedirectsTo;
use MichalSpacekCz\Http\SecurityHeaders\PermissionsPolicy\PermissionsPolicyDirective;
use MichalSpacekCz\Http\SecurityHeaders\PermissionsPolicy\PermissionsPolicyOrigin;
use MichalSpacekCz\Sitemap\InSitemap;
use MichalSpacekCz\Templating\DefaultTemplate;
use MichalSpacekCz\User\Manager;
use Nette\Application\BadRequestException;
use Nette\Application\Request;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\InvalidLinkException;
use Nette\Application\UI\Presenter;
use Nette\Forms\Form;
use Nette\Http\IResponse;
use Override;

/**
 * @property-read DefaultTemplate $template
 */
abstract class BasePresenter extends Presenter
{

	/** @var array<value-of<PermissionsPolicyDirective>, list<PermissionsPolicyOrigin|string>> */
	private array $permissionsPolicy = [];

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
		if ($this->authenticator->isForbidden() && $this->getRequest()->getMethod() !== Request::Forward) {
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
		$this->template->alternateLinks = $this->getAlternateLinks();
	}


	/**
	 * The page in every language it exists in, itself included, for the alternates in the head. Only for a page in the
	 * sitemap: a page left out is one like an error page, standing in for the page asked for, or one showing what the
	 * visitor's session holds, which the other language's host doesn't have, so its address in the other language
	 * shows something else. Empty for a page in one language only: the language switcher may lead to a list page then,
	 * but an alternate would claim it's this page.
	 *
	 * @return array<string, LocaleLink>
	 */
	protected function getAlternateLinks(): array
	{
		if (ActionMethodAttributes::find($this, InSitemap::class) === null) {
			return [];
		}
		try {
			$links = $this->localeLinkGenerator->linksForLocaleParams($this->getLocaleLinkAction(), $this->getLocaleLinkParams());
		} catch (InvalidLinkException) {
			return [];
		}
		return count($links) > 1 ? $links : [];
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


	#[Override]
	public function lastModified(string|int|DateTimeInterface|null $lastModified, ?string $etag = null, ?string $expire = null): void
	{
		$compression = ini_get('zlib.output_compression');
		ini_set('zlib.output_compression', false);
		parent::lastModified($lastModified, $etag, $expire);
		// If the response was HTTP 304 then the following line won't be reached and 304s won't be compressed
		ini_set('zlib.output_compression', $compression);
	}


	/**
	 * An action with #[CrossOriginRedirectsTo] sends a blocked cross-origin request to the attribute's
	 * destination, all other actions keep Nette's redirect back to the same action, see the attribute for why.
	 */
	#[Override]
	public function detectedCsrf(): void
	{
		$redirect = ActionMethodAttributes::find($this, CrossOriginRedirectsTo::class);
		if ($redirect === null) {
			parent::detectedCsrf();
			return;
		}
		try {
			$this->redirect($redirect->newInstance()->destination);
		} catch (InvalidLinkException $e) {
			throw new BadRequestException($e->getMessage(), previous: $e);
		}
	}


	/**
	 * Like sendJson() but accepts an already-serialized JSON string, avoiding a decode/re-encode round-trip
	 */
	protected function sendJsonString(string $json): never
	{
		$this->getHttpResponse()->setContentType('application/json');
		$this->sendResponse(new TextResponse($json));
	}


	protected function addPermissionsPolicy(PermissionsPolicyDirective $directive, PermissionsPolicyOrigin|string $origin): void
	{
		$this->permissionsPolicy[$directive->value][] = $origin;
	}


	/**
	 * @return array<value-of<PermissionsPolicyDirective>, list<PermissionsPolicyOrigin|string>>
	 */
	public function getPermissionsPolicy(): array
	{
		return $this->permissionsPolicy;
	}


	protected function createComponentCriticalCss(): CriticalCss
	{
		return $this->criticalCssFactory->create();
	}


	protected function createComponentTheme(): Form
	{
		return $this->themeFormFactory->create(function (): void {
			$this->httpResponse->setExpiration(null);
			$this->redirect('this');
		});
	}

}
