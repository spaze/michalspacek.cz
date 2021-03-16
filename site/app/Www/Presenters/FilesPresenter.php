<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use finfo;
use MichalSpacekCz\Training\Files;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Application\Responses\FileResponse;

class FilesPresenter extends BasePresenter
{

	private Files $trainingFiles;


	public function __construct(Files $trainingFiles)
	{
		$this->trainingFiles = $trainingFiles;
		parent::__construct();
	}


	/**
	 * @param string $filename
	 * @throws BadRequestException
	 * @throws AbortException
	 */
	public function actionTraining(string $filename): void
	{
		$session = $this->getSession('application');
		if (!$session->applicationId) {
			throw new BadRequestException("Unknown application id, missing or invalid token");
		}

		$file = $this->trainingFiles->getFile($session->applicationId, $session->token, $filename);
		if (!$file) {
			throw new BadRequestException("No file {$filename} for application id {$session->applicationId}");
		}
		$pathname = $file->info->getPathname();
		$fileInfo = new finfo(FILEINFO_MIME_TYPE);
		$this->sendResponse(new FileResponse($pathname, null, $fileInfo->file($pathname) ?: null));
	}


	/**
	 * @param string $filename
	 * @throws BadRequestException
	 */
	public function actionFile(string $filename): void
	{
		throw new BadRequestException("Cannot download {$filename}");
	}

}
