<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http;

use MichalSpacekCz\Application\LocaleLinkGenerator;
use MichalSpacekCz\Application\RouterFactory;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Http\UrlImmutable;
use Spaze\ContentSecurityPolicy\Config;

class SecurityHeaders
{

	private string $defaultDomain;
	private string $rootDomain;
	private IRequest $httpRequest;
	private IResponse $httpResponse;
	private Config $contentSecurityPolicy;
	private RouterFactory $routerFactory;
	private LocaleLinkGenerator $localeLinkGenerator;
	private string $presenterName;
	private string $actionName;

	/** @var array<string|string[]> */
	private array $featurePolicy;

	/** @var array<string|string[]> */
	private array $permissionsPolicy;


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
	 * @param array<string|null|string[]> $policy
	 */
	public function setPermissionsPolicy(array $policy): void
	{
		$featurePolicy = $permissionsPolicy = $policy;
		$this->normalizeFeaturePolicyValues($featurePolicy);
		$this->normalizePermissionsPolicyValues($permissionsPolicy);
		$this->featurePolicy = $featurePolicy;
		$this->permissionsPolicy = $permissionsPolicy;
	}


	/**
	 * @param array<string|null|string[]> $values
	 */
	private function normalizeFeaturePolicyValues(array &$values): void
	{
		foreach ($values as &$value) {
			if ($value === 'none' || $value === null) {
				$value = "'none'";
			} elseif ($value === 'self') {
				$value = "'self'";
			} elseif (is_array($value)) {
				$this->normalizeFeaturePolicyValues($value);
			} else {
				$value = trim($value);
			}
		}
	}


	/**
	 * @param array<string|null|string[]> $values
	 */
	private function normalizePermissionsPolicyValues(array &$values): void
	{
		foreach ($values as &$value) {
			if ($value === 'none' || $value === null) {
				$value = '';
			} elseif ($value === 'self') {
				$value = 'self';
			} elseif (is_array($value)) {
				$this->normalizePermissionsPolicyValues($value);
			} else {
				$value = trim($value);
				if ($value !== '') {
					$value = sprintf('"%s"', $value);
				}
			}
		}
	}


	private function getFeaturePolicyHeader(): string
	{
		$directives = [];
		foreach ($this->featurePolicy as $directive => $values) {
			if (is_array($values)) {
				$values = implode(' ', array_filter($values));
			}
			$directives[] = "$directive $values";
		}
		return implode('; ', $directives);
	}


	public function getPermissionsPolicyHeader(): string
	{
		$directives = [];
		foreach ($this->permissionsPolicy as $directive => $values) {
			if (is_array($values)) {
				$values = implode(' ', array_filter($values));
			}
			$directives[] = "$directive=({$values})";
		}
		return implode(', ', $directives);
	}


	public function sendHeaders(): void
	{
		$header = $this->contentSecurityPolicy->getHeader($this->presenterName, $this->actionName);
		if (!empty($header)) {
			$this->httpResponse->setHeader('Content-Security-Policy', $header);
		}

		$this->httpResponse->setHeader('Feature-Policy', $this->getFeaturePolicyHeader());
		$this->httpResponse->setHeader('Permissions-Policy', $this->getPermissionsPolicyHeader());
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
