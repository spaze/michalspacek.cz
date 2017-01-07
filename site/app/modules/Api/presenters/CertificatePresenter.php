<?php
namespace App\ApiModule\Presenters;

/**
 * Certificate presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class CertificatePresenter extends \App\WwwModule\Presenters\BasePresenter
{

	public function actionDefault()
	{
		foreach ($this->request->getPost('expiry') ?? [] as $cn => $expiry) {
			\Tracy\Debugger::log("OK $cn $expiry", 'cert');
		}
		foreach ($this->request->getPost('failure') ?? [] as $cn) {
			\Tracy\Debugger::log("FAIL $cn", 'cert');
		}
		$this->sendJson(['OK']);
	}

}
