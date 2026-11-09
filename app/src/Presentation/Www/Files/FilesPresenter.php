<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Www\Files;

use MichalSpacekCz\Presentation\Www\BasePresenter;
use MichalSpacekCz\Sitemap\NotInSitemap;
use MichalSpacekCz\Training\Files\TrainingFilesDownload;
use Nette\Application\BadRequestException;

final class FilesPresenter extends BasePresenter
{

	public function __construct(
		private readonly TrainingFilesDownload $trainingFilesDownload,
	) {
		parent::__construct();
	}


	#[NotInSitemap('a training material download, not a page')]
	public function actionTraining(string $filename): void
	{
		$this->sendResponse($this->trainingFilesDownload->getFileResponse($filename));
	}


	#[NotInSitemap('refuses every download, it has nothing to show')]
	public function actionFile(string $filename): void
	{
		throw new BadRequestException("Cannot download {$filename}");
	}

}
