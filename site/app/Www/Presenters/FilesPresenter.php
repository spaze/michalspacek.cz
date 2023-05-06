<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use finfo;
use MichalSpacekCz\Training\Files\TrainingFiles;
use Nette\Application\BadRequestException;
use Nette\Application\Responses\FileResponse;

class FilesPresenter extends BasePresenter
{

	public function __construct(
		private readonly TrainingFiles $trainingFiles,
	) {
		parent::__construct();
	}


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
		$pathname = $file->getFileInfo()->getPathname();
		$fileInfo = new finfo(FILEINFO_MIME_TYPE);
		$this->sendResponse(new FileResponse($pathname, null, $fileInfo->file($pathname) ?: null));
	}


	public function actionFile(string $filename): void
	{
		throw new BadRequestException("Cannot download {$filename}");
	}

}
