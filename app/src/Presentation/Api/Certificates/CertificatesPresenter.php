<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Api\Certificates;

use MichalSpacekCz\Http\HttpInput;
use MichalSpacekCz\Presentation\Api\BasePresenter;
use MichalSpacekCz\Tls\CertificateFactory;
use MichalSpacekCz\Tls\Certificates;
use MichalSpacekCz\Tls\Exceptions\SomeCertificatesLoggedToFileException;
use Nette\Security\AuthenticationException;
use Override;
use Throwable;
use Tracy\Debugger;

final class CertificatesPresenter extends BasePresenter
{

	public function __construct(
		private readonly Certificates $certificates,
		private readonly CertificateFactory $certificateFactory,
		private readonly HttpInput $httpInput,
	) {
		parent::__construct();
	}


	#[Override]
	protected function startup(): void
	{
		parent::startup();
		try {
			$this->certificates->authenticate($this->httpInput->getPostString('user') ?? '', $this->httpInput->getPostString('key') ?? '');
		} catch (AuthenticationException) {
			$this->sendJson(['status' => 'error', 'statusMessage' => 'Invalid credentials']);
		}
	}


	public function actionDefault(): never
	{
		$this->sendJson(['status' => 'ok', 'certificates' => $this->certificates->getNewest()]);
	}


	public function actionLogIssued(): void
	{
		$string = $this->httpInput->getPostString('certificate');
		if ($string === null) {
			$this->sendJson(['status' => 'error', 'statusMessage' => 'No certificate sent']);
		}
		$name = $this->httpInput->getPostString('name');
		if ($name === null) {
			$this->sendJson(['status' => 'error', 'statusMessage' => 'No certificate name sent']);
		}

		try {
			$cert = $this->certificateFactory->fromString($name, $string);
			$this->certificates->log($cert);
		} catch (SomeCertificatesLoggedToFileException) {
			$this->sendJson(['status' => 'error', 'statusMessage' => 'Some certs logged to file']);
		} catch (Throwable $e) {
			Debugger::log($e, Debugger::EXCEPTION);
			$this->sendJson(['status' => 'error', 'statusMessage' => 'Certificate processing failed']);
		}
		$this->sendJson([
			'status' => 'ok',
			'statusMessage' => 'Certificate reported successfully',
		]);
	}

}
