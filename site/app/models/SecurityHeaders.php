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

	/** @var \MichalSpacekCz\Application\RouterFactory */
	private $routerFactory;

	/** @var string */
	private $presenterName;

	/** @var string */
	private $actionName;

	/** @var array<string|string[]> */
	private $featurePolicies;


	/**
	 * @param \Nette\Http\IRequest $httpRequest
	 * @param \Nette\Http\IResponse $httpResponse
	 * @param \Spaze\ContentSecurityPolicy\Config $contentSecurityPolicy
	 * @param \MichalSpacekCz\Application\RouterFactory $routerFactory
	 */
	public function __construct(
		\Nette\Http\IRequest $httpRequest,
		\Nette\Http\IResponse $httpResponse,
		\Spaze\ContentSecurityPolicy\Config $contentSecurityPolicy,
		\MichalSpacekCz\Application\RouterFactory $routerFactory
	)
	{
		$this->httpRequest = $httpRequest;
		$this->httpResponse = $httpResponse;
		$this->contentSecurityPolicy = $contentSecurityPolicy;
		$this->routerFactory = $routerFactory;
	}


	public function setDefaultDomain(string $defaultDomain): void
	{
		$this->defaultDomain = $defaultDomain;
	}


	public function setRootDomain(string $rootDomain): void
	{
		$this->rootDomain = $rootDomain;
	}


	/**
	 * @param array<string|string[]> $policies
	 */
	public function setFeaturePolicy(array $policies): void
	{
		$result = $policies;
		foreach ($result as &$policy) {
			if ($policy === 'none') {
				$policy = "'none'";
			}
		}
		$this->featurePolicies = $result;
	}


	private function getFeaturePolicyHeader(): string
	{
		$directives = [];
		foreach ($this->featurePolicies as $directive => $values) {
			if (is_array($values)) {
				$values = implode(' ', $values);
			}
			$directives[] = "$directive $values";
		}
		return implode('; ', $directives);
	}


	public function sendHeaders(): void
	{
		$header = $this->contentSecurityPolicy->getHeader($this->presenterName, $this->actionName);
		if (!empty($header)) {
			$this->httpResponse->setHeader('Content-Security-Policy', $header);
		}

		$this->httpResponse->setHeader('Feature-Policy', $this->getFeaturePolicyHeader());
	}


	/**
	 * Set Content Security Policy.
	 *
	 * @param string $presenterName
	 * @param string $actionName
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
	 * Generates Access-Control-Allow-Origin header, if there's a Origin request header.
	 *
	 * @param string $scheme URL scheme
	 * @param string $host URL host
	 */
	public function accessControlAllowOrigin(string $scheme, string $host): void
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
