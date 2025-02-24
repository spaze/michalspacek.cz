<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use MichalSpacekCz\Training\Files\TrainingFilesDownload;
use Nette\Application\BadRequestException;

final class FilesPresenter extends BasePresenter
{

	public function __construct(
		private readonly TrainingFilesDownload $trainingFilesDownload,
	) {
		parent::__construct();
	}


	public function actionTraining(string $filename): void
	{
		$this->sendResponse($this->trainingFilesDownload->getFileResponse($filename));
	}


	public function actionFile(string $filename): void
	{
		throw new BadRequestException("Cannot download {$filename}");
	}

}
