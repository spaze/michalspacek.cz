<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http;

use MichalSpacekCz\Application\LocaleLinkGeneratorInterface;
use MichalSpacekCz\Http\ContentSecurityPolicy\CspValues;
use Nette\Application\Application;
use Nette\Application\UI\Presenter;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Http\UrlImmutable;
use Spaze\ContentSecurityPolicy\CspConfig;

class SecurityHeaders
{

	/** @var array<string|string[]> */
	private readonly array $permissionsPolicy;


	/**
	 * @param array<string|string[]> $permissionsPolicy
	 */
	public function __construct(
		private readonly IRequest $httpRequest,
		private readonly IResponse $httpResponse,
		private readonly Application $application,
		private readonly CspConfig $contentSecurityPolicy,
		private readonly LocaleLinkGeneratorInterface $localeLinkGenerator,
		array $permissionsPolicy,
	) {
		$this->permissionsPolicy = $this->normalizePermissionsPolicyValues($permissionsPolicy);
	}


	/**
	 * @param array<string|string[]> $values
	 * @return array<string|string[]>
	 */
	private function normalizePermissionsPolicyValues(array $values): array
	{
		foreach ($values as &$value) {
			if ($value === 'none') {
				$value = '';
			} elseif (is_array($value)) {
				$value = $this->normalizePermissionsPolicyValues($value);
			} elseif ($value !== 'self') {
				$value = trim($value);
				if ($value !== '') {
					$value = sprintf('"%s"', $value);
				}
			}
		}
		return $values;
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


	public function sendHeaders(CspValues $cspValues = CspValues::Specific): void
	{
		if ($cspValues === CspValues::Specific) {
			/** @var Presenter $presenter */
			$presenter = $this->application->getPresenter();
			$actionName = $presenter->getAction(true);
		} else {
			$actionName = $this->contentSecurityPolicy->getDefaultKey();
		}
		$header = $this->contentSecurityPolicy->getHeader($actionName);
		if (!empty($header)) {
			$this->httpResponse->setHeader('Content-Security-Policy', $header);
		}
		$header = $this->contentSecurityPolicy->getHeaderReportOnly($actionName);
		if ($header) {
			$this->httpResponse->setHeader('Content-Security-Policy-Report-Only', $header);
		}

		$this->httpResponse->setHeader('Permissions-Policy', $this->getPermissionsPolicyHeader());
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
