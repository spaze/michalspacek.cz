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
use stdClass;

final readonly class CertificatesApiClient
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
					'certificateName' => Expect::string()->required(),
					'certificateNameExt' => Expect::string()->required()->nullable(),
					'cn' => Expect::string()->required()->nullable(),
					'san' => Expect::listOf(Expect::string())->required()->nullable(),
					'notBefore' => Expect::string()->required(),
					'notBeforeTz' => Expect::string()->required(),
					'notAfter' => Expect::string()->required(),
					'notAfterTz' => Expect::string()->required(),
					'serialNumber' => Expect::string()->required()->nullable(),
					'now' => Expect::string()->required(),
					'nowTz' => Expect::string()->required(),
				]),
			),
		]);
		try {
			$data = $this->schemaProcessor->process($schema, $decoded);
			assert($data instanceof stdClass);
			assert(is_array($data->certificates) && array_is_list($data->certificates));
		} catch (ValidationException $e) {
			throw new CertificatesApiException(sprintf('Cannot validate response from %s (`%s`): %s', $request->getUrl(), $json, implode(', ', $e->getMessages())), previous: $e);
		}
		if ($data->status !== 'ok') {
			throw new CertificatesApiException(sprintf('Response from %s (`%s`) not ok', $request->getUrl(), $json));
		}
		foreach ($data->certificates as $details) {
			assert($details instanceof stdClass);
			assert(is_string($details->certificateName));
			assert(is_string($details->certificateNameExt) || $details->certificateNameExt === null);
			assert(is_string($details->cn) || $details->cn === null);
			assert(is_array($details->san) && array_is_list($details->san) || $details->san === null);
			/** @var list<string>|null $san */
			$san = $details->san;
			assert(is_string($details->notBefore));
			assert(is_string($details->notBeforeTz));
			assert(is_string($details->notAfter));
			assert(is_string($details->notAfterTz));
			assert(is_string($details->serialNumber) || $details->serialNumber === null);
			assert(is_string($details->now));
			assert(is_string($details->nowTz));
			$certificates[] = $this->certificateFactory->get(
				$details->certificateName,
				$details->certificateNameExt,
				$details->cn,
				$san,
				$details->notBefore,
				$details->notBeforeTz,
				$details->notAfter,
				$details->notAfterTz,
				$details->serialNumber,
				$details->now,
				$details->nowTz,
			);
		}
		return $certificates;
	}

}
