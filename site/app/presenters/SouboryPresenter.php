<?php
use \Nette\Application\BadRequestException,
	\Nette\Application\Responses\FileResponse,
	\Nette\Http\Response;

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
			throw new BadRequestException("Unknown application id, missing or invalid token", Response::S404_NOT_FOUND);
		}

		$file = $this->trainings->getFile($session->applicationId, $session->token, $filename);
		if (!$file) {
			throw new BadRequestException("No file {$filename} for application id {$session->applicationId}", Response::S404_NOT_FOUND);
		}

		$path = "{$file->dirName}/{$file->fileName}";
		$this->sendResponse(new FileResponse($path, null, \Nette\Utils\MimeTypeDetector::fromFile($path)));
	}


	public function actionSoubor($filename)
	{
	}


}
