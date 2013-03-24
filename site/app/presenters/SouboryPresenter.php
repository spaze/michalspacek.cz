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
		$session = $this->getSession('application');
		if (!$session->applicationId) {
			throw new \Nette\Application\BadRequestException("Unknown application id, missing or invalid token", \Nette\Http\Response::S404_NOT_FOUND);
		}

		$file = $this->trainings->getFile($session->applicationId, $session->token, $filename);
		if (!$file) {
			throw new \Nette\Application\BadRequestException("No file {$filename} for application id {$session->applicationId}", \Nette\Http\Response::S404_NOT_FOUND);
		}

		echo "download files/skoleni/{$file->dirName}/{$file->fileName}";
		$this->terminate();
	}


	public function actionSoubor($filename)
	{
	}


}
