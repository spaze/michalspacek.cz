<?php
declare(strict_types = 1);

namespace App\ApiModule\Presenters;

/**
 * Certificate presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class CertificatePresenter extends \App\WwwModule\Presenters\BasePresenter
{

	/** @var \MichalSpacekCz\Api\Certificates */
	protected $certificates;


	/**
	 * @param \MichalSpacekCz\Api\Certificates $certificates
	 */
	public function __construct(\MichalSpacekCz\Api\Certificates $certificates)
	{
		$this->certificates = $certificates;
		parent::__construct();
	}


	public function actionDefault(): void
	{
		try {
			$this->certificates->authenticate($this->request->getPost('user'), $this->request->getPost('key'));

			$count = $this->certificates->log($this->request->getPost('certs') ?? [], $this->request->getPost('failure') ?? []);
			$this->sendJson([
				'status' => 'ok',
				'statusMessage' => 'Certificates reported successfully',
				'count' => $count,
			]);
		} catch (\Nette\Security\AuthenticationException $e) {
			$this->sendJson(['status' => 'error', 'statusMessage' => 'Invalid credentials']);
		}
	}

}
