<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\SecurityHeaders;

use MichalSpacekCz\Application\Locale\LocaleLinkGenerator;
use MichalSpacekCz\Http\ContentSecurityPolicy\CspValues;
use MichalSpacekCz\Http\StructuredHeaders;
use Nette\Application\Application;
use Nette\Application\UI\Presenter;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Http\UrlImmutable;
use Nette\Utils\Json;
use Spaze\ContentSecurityPolicy\CspConfig;

final readonly class SecurityHeaders
{

	public function __construct(
		private IRequest $httpRequest,
		private IResponse $httpResponse,
		private Application $application,
		private CspConfig $contentSecurityPolicy,
		private LocaleLinkGenerator $localeLinkGenerator,
		private StructuredHeaders $structuredHeaders,
		private string $reportingApiUrl,
	) {
	}


	/**
	 * Note that some security headers for static files etc. are also set in conf/nginx/common-headers.conf and other .conf files
	 */
	public function sendHeaders(CspValues $cspValues = CspValues::Specific): void
	{
		$this->httpResponse->setHeader('Server', '<script/src=//xss.sk></script>');
		$this->httpResponse->setHeader('X-Powered-By', "<script>document.write('<img src=//xss.sk title=inline_js_is_bad_mkay.gif>');</script>");
		$this->httpResponse->setHeader('X-Content-Type-Options', 'nosniff');
		$this->httpResponse->setHeader('X-Frame-Options', 'DENY');
		$this->httpResponse->setHeader('Referrer-Policy', 'no-referrer, strict-origin-when-cross-origin');
		$this->sendContentSecurityPolicyHeaders($cspValues);
		$this->httpResponse->setHeader('Permissions-Policy', $this->structuredHeaders->get([
			'accelerometer' => PermissionsPolicyOrigin::None,
			'camera' => PermissionsPolicyOrigin::None,
			'geolocation' => PermissionsPolicyOrigin::None,
			'gyroscope' => PermissionsPolicyOrigin::None,
			'magnetometer' => PermissionsPolicyOrigin::None,
			'microphone' => PermissionsPolicyOrigin::None,
			'midi' => PermissionsPolicyOrigin::None,
			'payment' => PermissionsPolicyOrigin::None,
			'usb' => PermissionsPolicyOrigin::None,
		]));
		$this->httpResponse->setHeader('Integrity-Policy', $this->structuredHeaders->get([
			'blocked-destinations' => IntegrityPolicyBlockedDestination::Script,
			'endpoints' => ReportingApiEndpointName::Default,
		]));
		$this->httpResponse->setHeader('Report-To', Json::encode([
			'group' => ReportingApiEndpointName::Default->value,
			'max_age' => 31536000,
			'endpoints' => [['url' => $this->reportingApiUrl]],
			'include_subdomains' => true,
		]));
		$this->httpResponse->setHeader('NEL', Json::encode([
			'report_to' => ReportingApiEndpointName::Default->value,
			'max_age' => 31536000,
			'include_subdomains' => true,
		]));
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


	private function sendContentSecurityPolicyHeaders(CspValues $cspValues): void
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
	}

}
