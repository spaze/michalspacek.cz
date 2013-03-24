<?php
/**
 * Soubory presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class SouboryPresenter extends BasePresenter
{


	public function actionSkoleni($filename)
	{
		echo 'download';
		$this->terminate();
	}


	public function actionSoubor($filename)
	{
	}


}
