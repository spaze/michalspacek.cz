<?php
declare(strict_types = 1);

namespace MichalSpacekCz;

use MichalSpacekCz\Application\LocaleLinkGenerator;
use MichalSpacekCz\Application\RouterFactory;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Http\UrlImmutable;
use Spaze\ContentSecurityPolicy\Config;

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

	/** @var IRequest */
	protected $httpRequest;

	/** @var IResponse */
	protected $httpResponse;

	/** @var Config */
	protected $contentSecurityPolicy;

	/** @var RouterFactory */
	private $routerFactory;

	/** @var string */
	private $presenterName;

	/** @var string */
	private $actionName;

	/** @var array<string|string[]> */
	private $featurePolicies;

	/** @var LocaleLinkGenerator */
	private $localeLinkGenerator;


	public function __construct(
		IRequest $httpRequest,
		IResponse $httpResponse,
		Config $contentSecurityPolicy,
		RouterFactory $routerFactory,
		LocaleLinkGenerator $localeLinkGenerator
	) {
		$this->httpRequest = $httpRequest;
		$this->httpResponse = $httpResponse;
		$this->contentSecurityPolicy = $contentSecurityPolicy;
		$this->routerFactory = $routerFactory;
		$this->localeLinkGenerator = $localeLinkGenerator;
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
	 * Generates Access-Control-Allow-Origin header, if there's a Origin request header and it matches any source link.
	 *
	 * @param string $source URL to allow in format "[[[module:]presenter:]action] [#fragment]"
	 */
	public function accessControlAllowOrigin(string $source): void
	{
		$this->localeLinkGenerator->allLinks($source);
		$origin = $this->httpRequest->getHeader('Origin');
		if ($origin !== null) {
			foreach ($this->getOrigins($source) as $from) {
				if ($from === $origin) {
					$this->httpResponse->setHeader('Access-Control-Allow-Origin', $origin);
				}
			}
		}
	}


	/**
	 * @param string $link
	 * @return string[]
	 */
	private function getOrigins(string $link): array
	{
		$origins = $this->localeLinkGenerator->allLinks($link);
		foreach ($origins as &$url) {
			$url = (new UrlImmutable($url))->getHostUrl();
		}
		return $origins;
	}

}
