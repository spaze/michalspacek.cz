<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use MichalSpacekCz\DateTime\Exceptions\CannotParseDateTimeException;
use MichalSpacekCz\Http\HttpStreamContext;
use MichalSpacekCz\Tls\Exceptions\CertificatesApiException;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\InvalidLinkException;
use Nette\Utils\Helpers;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

class CertificatesApiClient
{

	public function __construct(
		private LinkGenerator $linkGenerator,
		private CertificateFactory $certificateFactory,
		private HttpStreamContext $httpStreamContext,
	) {
	}


	/**
	 * @return array<int, Certificate>
	 * @throws Exceptions\CertificateException
	 * @throws CannotParseDateTimeException
	 * @throws InvalidLinkException
	 * @throws JsonException
	 * @throws CertificatesApiException
	 */
	public function getLoggedCertificates(): array
	{
		$url = $this->linkGenerator->link('Api:Certificates:');
		$json = @file_get_contents($url, context: $this->httpStreamContext->create(  // intentionally @, warning converted to exception
			__METHOD__,
			[
				'method' => 'POST',
				'header'  => 'Content-type: application/x-www-form-urlencoded',
				'content' => $this->getPostData(),
			],
		));
		if (!$json) {
			throw new CertificatesApiException(sprintf('Failure getting data from %s: %s', $url, Helpers::getLastError()));
		}
		$certificates = [];
		$decoded = Json::decode($json, Json::FORCE_ARRAY);
		if (!is_array($decoded)) {
			throw new CertificatesApiException(sprintf('Decoded response type from %s is %s (`%s`) not array', $url, gettype($decoded), $json));
		}
		if (!isset($decoded['status'])) {
			throw new CertificatesApiException(sprintf('Decoded response from %s (`%s`) has no field `status`', $url, $json));
		}
		if ($decoded['status'] !== 'ok') {
			throw new CertificatesApiException(sprintf('Response from %s (`%s`) not ok', $url, $json));
		}
		if (!isset($decoded['certificates'])) {
			throw new CertificatesApiException(sprintf('Decoded response from %s (`%s`) has no field `certificates`', $url, $json));
		}
		foreach ($decoded as $details) {
			$certificates[] = $this->certificateFactory->fromArray($details);
		}
		return $certificates;
	}


	private function getPostData(): string
	{
		$postData = [
			'user' => $_SERVER['CERTMONITOR_USER'],
			'key' => $_SERVER['CERTMONITOR_KEY'],
		];
		return http_build_query($postData);
	}

}
