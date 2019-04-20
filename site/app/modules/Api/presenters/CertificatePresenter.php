<?php
declare(strict_types = 1);

namespace App\ApiModule\Presenters;

use App\WwwModule\Presenters\BasePresenter;
use MichalSpacekCz\Certificates;
use Nette\Security\AuthenticationException;
use RuntimeException;

/**
 * Certificate presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class CertificatePresenter extends BasePresenter
{

	/** @var Certificates */
	protected $certificates;


	/**
	 * @param Certificates $certificates
	 */
	public function __construct(Certificates $certificates)
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
		} catch (AuthenticationException $e) {
			$this->sendJson(['status' => 'error', 'statusMessage' => 'Invalid credentials']);
		} catch (RuntimeException $e) {
			$this->sendJson(['status' => 'error', 'statusMessage' => 'Some certs logged to file']);
		}
	}

}
