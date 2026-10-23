<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Api\Certificates;

use MichalSpacekCz\Api\Endpoint\EndpointRequiresAuthentication;
use MichalSpacekCz\DateTime\Exceptions\CannotParseDateTimeException;
use MichalSpacekCz\Http\HttpInput;
use MichalSpacekCz\Presentation\Api\BasePresenter;
use MichalSpacekCz\Tls\CertificateFactory;
use MichalSpacekCz\Tls\Certificates;
use MichalSpacekCz\Tls\Exceptions\OpenSslException;
use MichalSpacekCz\Tls\Exceptions\OpenSslX509ParseException;
use MichalSpacekCz\Tls\Exceptions\SomeCertificatesLoggedToFileException;
use Nette\Http\IResponse;
use Nette\Security\AuthenticationException;
use Override;
use Throwable;
use Tracy\Debugger;

/**
 * The HTTP status tells a client whether its request succeeded, so a script needs nothing but that, and the JSON body
 * says why for whoever reads the script's output. A 503 means the database was down and the request can be repeated
 * later, any other error means the request itself has to change. Every answer carries a `message` for humans.
 */
#[EndpointRequiresAuthentication]
final class CertificatesPresenter extends BasePresenter
{

	public function __construct(
		private readonly Certificates $certificates,
		private readonly CertificateFactory $certificateFactory,
		private readonly HttpInput $httpInput,
		private readonly IResponse $httpResponse,
	) {
		parent::__construct();
	}


	#[Override]
	protected function startupApi(): void
	{
		try {
			$this->certificates->authenticate($this->httpInput->getPostString('user') ?? '', $this->httpInput->getPostString('key') ?? '');
		} catch (AuthenticationException) {
			$this->sendError(IResponse::S403_Forbidden, 'Invalid credentials');
		}
	}


	public function actionDefault(): never
	{
		$this->sendJson(['message' => 'OK', 'certificates' => $this->certificates->getNewest()]);
	}


	public function actionLogIssued(): void
	{
		$string = $this->httpInput->getPostString('certificate');
		if ($string === null || $string === '') {
			$this->sendError(IResponse::S400_BadRequest, 'No certificate sent');
		}
		$name = $this->httpInput->getPostString('name');
		if ($name === null || $name === '') {
			$this->sendError(IResponse::S400_BadRequest, 'No certificate name sent');
		}

		try {
			$cert = $this->certificateFactory->fromString($name, $string);
			$this->certificates->log($cert);
		} catch (OpenSslException | OpenSslX509ParseException | CannotParseDateTimeException $e) {
			Debugger::log($e, Debugger::EXCEPTION);
			$this->sendError(IResponse::S400_BadRequest, 'Cannot parse the certificate');
		} catch (SomeCertificatesLoggedToFileException) {
			$this->sendError(IResponse::S503_ServiceUnavailable, 'Some certs logged to file');
		} catch (Throwable $e) {
			Debugger::log($e, Debugger::EXCEPTION);
			$this->sendError(IResponse::S500_InternalServerError, 'Certificate processing failed');
		}
		$this->sendJson(['message' => 'Certificate reported successfully']);
	}


	private function sendError(int $httpCode, string $message): never
	{
		$this->httpResponse->setCode($httpCode);
		$this->sendJson(['message' => $message]);
	}

}
