<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use MichalSpacekCz\Application\LinkGenerator;
use MichalSpacekCz\Application\ServerEnv;
use MichalSpacekCz\DateTime\Exceptions\CannotParseDateTimeException;
use MichalSpacekCz\Http\Client\HttpClient;
use MichalSpacekCz\Http\Client\HttpClientRequest;
use MichalSpacekCz\Http\Exceptions\HttpClientRequestException;
use MichalSpacekCz\Tls\Exceptions\CertificatesApiException;
use Nette\Application\UI\InvalidLinkException;
use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nette\Schema\ValidationException;
use Nette\Utils\Helpers;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

readonly class CertificatesApiClient
{

	public function __construct(
		private LinkGenerator $linkGenerator,
		private CertificateFactory $certificateFactory,
		private HttpClient $httpClient,
		private Processor $schemaProcessor,
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
		$request = new HttpClientRequest($this->linkGenerator->link('Api:Certificates:'));
		$request->setUserAgent(__METHOD__);
		try {
			$json = $this->httpClient->postForm($request, [
				'user' => ServerEnv::tryGetString('CERTMONITOR_USER') ?? '',
				'key' => ServerEnv::tryGetString('CERTMONITOR_KEY') ?? '',
			])->getBody();
		} catch (HttpClientRequestException $e) {
			throw new CertificatesApiException(sprintf('Failure getting data from %s: %s', $request->getUrl(), Helpers::getLastError()), previous: $e);
		}
		$certificates = [];
		$decoded = Json::decode($json, forceArrays: true);
		$schema = Expect::structure([
			'status' => Expect::string(),
			'certificates' => Expect::listOf(
				Expect::structure([
					'commonName' => Expect::string()->required(),
					'commonNameExt' => Expect::string()->required()->nullable(),
					'notBefore' => Expect::string()->required(),
					'notBeforeTz' => Expect::string()->required(),
					'notAfter' => Expect::string()->required(),
					'notAfterTz' => Expect::string()->required(),
					'expiringThreshold' => Expect::int()->required(),
					'serialNumber' => Expect::string()->required()->nullable(),
					'now' => Expect::string()->required(),
					'nowTz' => Expect::string()->required(),
				]),
			),
		]);
		try {
			/** @var object{status:string, certificates:list<object{commonName:string, commonNameExt:string|null, notBefore:string, notBeforeTz:string, notAfter:string, notAfterTz:string, expiringThreshold:int, serialNumber:string|null, now:string, nowTz:string}>} $data */
			$data = $this->schemaProcessor->process($schema, $decoded);
		} catch (ValidationException $e) {
			throw new CertificatesApiException(sprintf('Cannot validate response from %s (`%s`): %s', $request->getUrl(), $json, implode(', ', $e->getMessages())), previous: $e);
		}
		if ($data->status !== 'ok') {
			throw new CertificatesApiException(sprintf('Response from %s (`%s`) not ok', $request->getUrl(), $json));
		}
		foreach ($data->certificates as $details) {
			$certificates[] = $this->certificateFactory->get(
				$details->commonName,
				$details->commonNameExt,
				$details->notBefore,
				$details->notBeforeTz,
				$details->notAfter,
				$details->notAfterTz,
				$details->expiringThreshold,
				$details->serialNumber,
				$details->now,
				$details->nowTz,
			);
		}
		return $certificates;
	}

}
