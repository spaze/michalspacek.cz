<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http;

use MichalSpacekCz\Application\LocaleLinkGenerator;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Http\UrlImmutable;
use Spaze\ContentSecurityPolicy\Config;

class SecurityHeaders
{

	private string $presenterName;
	private string $actionName;

	/** @var array<string|string[]> */
	private array $permissionsPolicy;


	public function __construct(
		private readonly IRequest $httpRequest,
		private readonly IResponse $httpResponse,
		private readonly Config $contentSecurityPolicy,
		private readonly LocaleLinkGenerator $localeLinkGenerator,
	) {
	}


	/**
	 * @param array<string|null|string[]> $policy
	 */
	public function setPermissionsPolicy(array $policy): void
	{
		$permissionsPolicy = $policy;
		$this->normalizePermissionsPolicyValues($permissionsPolicy);
		$this->permissionsPolicy = $permissionsPolicy;
	}


	/**
	 * @param array<string|null|string[]> $values
	 */
	private function normalizePermissionsPolicyValues(array &$values): void
	{
		foreach ($values as &$value) {
			if ($value === 'none' || $value === null) {
				$value = '';
			} elseif (is_array($value)) {
				$this->normalizePermissionsPolicyValues($value);
			} elseif ($value !== 'self') {
				$value = trim($value);
				if ($value !== '') {
					$value = sprintf('"%s"', $value);
				}
			}
		}
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
		$header = $this->contentSecurityPolicy->getHeaderReportOnly($this->presenterName, $this->actionName);
		if ($header) {
			$this->httpResponse->setHeader('Content-Security-Policy-Report-Only', $header);
		}

		$this->httpResponse->setHeader('Permissions-Policy', $this->getPermissionsPolicyHeader());
	}


	public function setCsp(string $presenterName, string $actionName): self
	{
		$this->presenterName = $presenterName;
		$this->actionName = $actionName;
		return $this;
	}


	public function setDefaultCsp(): self
	{
		$this->presenterName = $this->actionName = $this->contentSecurityPolicy->getDefaultKey();
		return $this;
	}


	/**
	 * Generates Access-Control-Allow-Origin header, if there's an Origin request header, and it matches any source link.
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
