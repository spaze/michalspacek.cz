<?php
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

	/** @var array of host => headers */
	protected $extraHeaders = array();

	/** @var \Nette\Http\IRequest */
	protected $httpRequest;

	/** @var \Nette\Http\IResponse */
	protected $httpResponse;

	/** @var ContentSecurityPolicy */
	protected $contentSecurityPolicy;

	/** @var PublicKeyPins */
	protected $publicKeyPins;

	/** @var \MichalSpacekCz\Application\RouterFactory */
	private $routerFactory;


	/**
	 * @param \Nette\Http\IRequest $httpRequest
	 * @param \Nette\Http\IResponse $httpResponse
	 * @param \MichalSpacek\ContentSecurityPolicy $contentSecurityPolicy
	 * @param \MichalSpacek\PublicKeyPins $publicKeyPins
	 */
	public function __construct(
		\Nette\Http\IRequest $httpRequest,
		\Nette\Http\IResponse $httpResponse,
		ContentSecurityPolicy $contentSecurityPolicy,
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


	public function setDefaultDomain($defaultDomain)
	{
		$this->defaultDomain = $defaultDomain;
	}


	public function setRootDomain($rootDomain)
	{
		$this->rootDomain = $rootDomain;
	}


	public function setExtraHeaders($extraHeaders)
	{
		foreach ($extraHeaders as $host => $headers) {
			$this->extraHeaders[$host] = $headers;
		}
	}


	public function sendHeaders()
	{
		if ($this->httpRequest->isSecured()) {
			$this->httpResponse->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
		}

		$host = $this->getHost();
		$header = $this->publicKeyPins->getHeader($host);
		if ($header !== false) {
			$this->httpResponse->setHeader('Public-Key-Pins-Report-Only', $header);
		}

		if (isset($this->extraHeaders[$host])) {
			foreach ($this->extraHeaders[$host] as $name => $value) {
				$this->httpResponse->setHeader($name, $value);
			}
		}
	}


	/**
	 * Send Content Security Policy header.
	 */
	public function sendCspHeader($presenter)
	{
		$header = $this->contentSecurityPolicy->getHeader($presenter);
		if ($header !== false) {
			$this->httpResponse->setHeader('Content-Security-Policy', $header);
		}
	}


	/**
	 * Get host.
	 *
	 * @return string
	 */
	private function getHost()
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
	public function accessControlAllowOrigin($scheme, $host)
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
