<?php
declare(strict_types = 1);

namespace MichalSpacekCz;

/**
 * SecurityHeaders service.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class SecurityHeaders
{

	/** @var string */
	protected $defaultDomain;

	/** @var string */
	protected $rootDomain;

	/** @var \Nette\Http\IRequest */
	protected $httpRequest;

	/** @var \Nette\Http\IResponse */
	protected $httpResponse;

	/** @var \Spaze\ContentSecurityPolicy\Config */
	protected $contentSecurityPolicy;

	/** @var PublicKeyPins */
	protected $publicKeyPins;

	/** @var \MichalSpacekCz\Application\RouterFactory */
	private $routerFactory;

	/** @var string */
	private $presenterName;

	/** @var string*/
	private $actionName;


	/**
	 * @param \Nette\Http\IRequest $httpRequest
	 * @param \Nette\Http\IResponse $httpResponse
	 * @param \Spaze\ContentSecurityPolicy\Config $contentSecurityPolicy
	 * @param \MichalSpacek\PublicKeyPins $publicKeyPins
	 */
	public function __construct(
		\Nette\Http\IRequest $httpRequest,
		\Nette\Http\IResponse $httpResponse,
		\Spaze\ContentSecurityPolicy\Config $contentSecurityPolicy,
		PublicKeyPins $publicKeyPins,
		\MichalSpacekCz\Application\RouterFactory $routerFactory
	)
	{
		$this->httpRequest = $httpRequest;
		$this->httpResponse = $httpResponse;
		$this->contentSecurityPolicy = $contentSecurityPolicy;
		$this->publicKeyPins = $publicKeyPins;
		$this->routerFactory = $routerFactory;
	}


	public function setDefaultDomain(string $defaultDomain)
	{
		$this->defaultDomain = $defaultDomain;
	}


	public function setRootDomain(string $rootDomain)
	{
		$this->rootDomain = $rootDomain;
	}


	public function sendHeaders()
	{
		$header = $this->contentSecurityPolicy->getHeader($this->presenterName, $this->actionName);
		if ($header !== false) {
			$this->httpResponse->setHeader('Content-Security-Policy', $header);
		}

		if ($this->httpRequest->isSecured()) {
			$this->httpResponse->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
		}

		$host = $this->getHost();
		$header = $this->publicKeyPins->getHeader($host);
		if ($header !== false) {
			$this->httpResponse->setHeader('Public-Key-Pins-Report-Only', $header);
		}
	}


	/**
	 * Set Content Security Policy.
	 *
	 * @param string $presenter
	 * @param string $action
	 * @return self
	 */
	public function setCsp(string $presenterName, string $actionName): self
	{
		$this->presenterName = $presenterName;
		$this->actionName = $actionName;
		return $this;
	}


	/**
	 * Set default Content Security Policy.
	 *
	 * @return self
	 */
	public function setDefaultCsp(): self
	{
		$this->presenterName = $this->actionName = $this->contentSecurityPolicy->getDefaultKey();
		return $this;
	}


	/**
	 * Get host.
	 *
	 * @return string
	 */
	private function getHost(): string
	{
		if ($this->httpRequest->getUrl()->getHost() === $this->rootDomain) {
			$host = $this->defaultDomain;
		} else {
			$host = str_replace(".{$this->rootDomain}", '', $this->httpRequest->getUrl()->getHost());
		}
		return $host;
	}


	/**
	 * Generates Access-Control-Allow-Origin header, if there's a Origin request header.
	 *
	 * @param string $scheme URL scheme
	 * @param string $host URL host
	 */
	public function accessControlAllowOrigin(string $scheme, string $host)
	{
		$origin = $this->httpRequest->getHeader('Origin');
		if ($origin !== null) {
			foreach ($this->routerFactory->getLocaleRootDomainMapping() as $tld) {
				if ("{$scheme}://{$host}.{$tld}" === $origin) {
					$this->httpResponse->setHeader('Access-Control-Allow-Origin', $origin);
				}
			}
		}
	}

}
