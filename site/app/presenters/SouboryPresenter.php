<?php
/**
 * Soubory presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class SouboryPresenter extends BasePresenter
{


	public function actionSkoleni($date, $filename)
	{
		echo 'download';
		$this->terminate();
	}


	public function actionSoubor($filename)
	{
	}


}
