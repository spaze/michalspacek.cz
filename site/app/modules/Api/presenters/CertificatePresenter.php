<?php
namespace App\ApiModule\Presenters;

/**
 * Certificate presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class CertificatePresenter extends \App\Presenters\BasePresenter
{

	public function actionDefault()
	{
		foreach ($this->request->getPost('expiry') as $cn => $expiry) {
			\Tracy\Debugger::log("$cn $expiry", 'cert');
		}
		$this->sendJson(['OK']);
	}

}
