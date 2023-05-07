<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Api\Presenters;

use MichalSpacekCz\Tls\Certificates;
use MichalSpacekCz\Www\Presenters\BasePresenter;
use Nette\Security\AuthenticationException;
use RuntimeException;

class CertificatesPresenter extends BasePresenter
{

	public function __construct(
		private readonly Certificates $certificates,
	) {
		parent::__construct();
	}


	protected function startup(): void
	{
		parent::startup();
		try {
			$this->certificates->authenticate($this->request->getPost('user') ?? '', $this->request->getPost('key') ?? '');
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
		try {
			$count = $this->certificates->log($this->request->getPost('certs') ?? [], $this->request->getPost('failure') ?? []);
			$this->sendJson([
				'status' => 'ok',
				'statusMessage' => 'Certificates reported successfully',
				'count' => $count,
			]);
		} catch (RuntimeException) {
			$this->sendJson(['status' => 'error', 'statusMessage' => 'Some certs logged to file']);
		}
	}

}
