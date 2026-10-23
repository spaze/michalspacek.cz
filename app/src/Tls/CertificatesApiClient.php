<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use MichalSpacekCz\Application\LinkGenerator;
use MichalSpacekCz\Application\ServerEnv;
use MichalSpacekCz\DateTime\Exceptions\CannotParseDateTimeException;
use MichalSpacekCz\Http\Client\HttpClient;
use MichalSpacekCz\Http\Client\HttpClientRequest;
use MichalSpacekCz\Http\Exceptions\HttpClientRequestException;
use MichalSpacekCz\Http\Exceptions\HttpClientResponseStatusLineMissingException;
use MichalSpacekCz\Tls\Exceptions\CertificatesApiException;
use Nette\Application\UI\InvalidLinkException;
use Nette\Http\IResponse;
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
	 * @throws CertificatesApiException
	 */
	public function getLoggedCertificates(): array
	{
		$request = new HttpClientRequest($this->linkGenerator->link('Api:Certificates:'));
		$request->setUserAgent(__METHOD__);
		$request->setIgnoreHttpErrors(true); // an error answer carries its HTTP status, the JSON body says why, so read the body instead of failing on the status
		try {
			$response = $this->httpClient->postForm($request, [
				'user' => ServerEnv::tryGetString('CERTMONITOR_USER') ?? '',
				'key' => ServerEnv::tryGetString('CERTMONITOR_KEY') ?? '',
			]);
		} catch (HttpClientRequestException $e) {
			throw new CertificatesApiException(sprintf('Failure getting data from %s: %s', $request->getUrl(), Helpers::getLastError()), previous: $e);
		}
		try {
			$statusCode = $response->getStatusCode();
		} catch (HttpClientResponseStatusLineMissingException $e) {
			throw new CertificatesApiException($e->getMessage(), previous: $e);
		}
		$json = $response->getBody();
		$certificates = [];
		// The HTTP status says whether the answer is the list or an error, the body carries a message either way,
		// and nothing unknown is let through: the API lives in this repository too, so an unknown field is drift to catch
		$schema = Expect::structure([
			'message' => Expect::string()->required(),
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
			$data = $this->schemaProcessor->process($schema, Json::decode($json, forceArrays: true));
			assert($data instanceof stdClass);
			assert(is_string($data->message));
			assert(is_array($data->certificates) && array_is_list($data->certificates));
		} catch (JsonException | ValidationException $e) {
			$reason = $e instanceof ValidationException ? implode(', ', $e->getMessages()) : $e->getMessage();
			throw new CertificatesApiException(sprintf('Cannot read response from %s, HTTP %d (`%s`): %s', $request->getUrl(), $statusCode, $json, $reason), previous: $e);
		}
		if ($statusCode !== IResponse::S200_OK) {
			throw new CertificatesApiException(sprintf('Response from %s not ok, HTTP %d: %s', $request->getUrl(), $statusCode, $data->message));
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
