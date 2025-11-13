<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http;

use MichalSpacekCz\Application\Locale\LocaleLinkGenerator;
use MichalSpacekCz\Http\ContentSecurityPolicy\CspValues;
use MichalSpacekCz\Utils\Arrays;
use Nette\Application\Application;
use Nette\Application\UI\Presenter;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Http\UrlImmutable;
use Spaze\ContentSecurityPolicy\CspConfig;

final readonly class SecurityHeaders
{

	/**
	 * @param array<string, string|list<string>> $permissionsPolicy
	 */
	public function __construct(
		private IRequest $httpRequest,
		private IResponse $httpResponse,
		private Application $application,
		private CspConfig $contentSecurityPolicy,
		private LocaleLinkGenerator $localeLinkGenerator,
		private array $permissionsPolicy,
	) {
	}


	private function normalizePermissionsPolicyValue(string $value): string
	{
		if ($value === 'none') {
			$value = '';
		} elseif ($value !== 'self') {
			$value = trim($value);
			if ($value !== '') {
				$value = sprintf('"%s"', $value);
			}
		}
		return $value;
	}


	private function getPermissionsPolicyHeader(): string
	{
		$directives = [];
		foreach ($this->permissionsPolicy as $directive => $values) {
			if (is_array($values)) {
				$values = implode(' ', Arrays::filterEmpty(array_map($this->normalizePermissionsPolicyValue(...), $values)));
			} else {
				$values = $this->normalizePermissionsPolicyValue($values);
			}
			$directives[] = sprintf('%s=(%s)', $directive, $values);
		}
		return implode(', ', $directives);
	}


	public function sendHeaders(CspValues $cspValues = CspValues::Specific): void
	{
		$presenter = $this->application->getPresenter();
		if ($cspValues === CspValues::Specific && $presenter instanceof Presenter) {
			$actionName = $presenter->getAction(true);
		} else {
			$actionName = $this->contentSecurityPolicy->getDefaultKey();
		}
		$header = $this->contentSecurityPolicy->getHeader($actionName);
		if ($header !== '') {
			$this->httpResponse->setHeader('Content-Security-Policy', $header);
		}
		$header = $this->contentSecurityPolicy->getHeaderReportOnly($actionName);
		if ($header !== '') {
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
