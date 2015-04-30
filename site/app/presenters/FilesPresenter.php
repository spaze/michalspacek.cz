<?php
use Nette\Application\BadRequestException,
	Nette\Application\Responses\FileResponse,
	Nette\Http\Response;

/**
 * Files presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class FilesPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Files */
	protected $files;

	/** @var \MichalSpacekCz\Training\Files */
	protected $trainingFiles;


	/**
	 * @param \Nette\Localization\ITranslator $translator
	 * @param \MichalSpacekCz\Files
	 * @param \MichalSpacekCz\Training\Files $trainingFiles
	 */
	public function __construct(
		Nette\Localization\ITranslator $translator,
		MichalSpacekCz\Files $files,
		MichalSpacekCz\Training\Files $trainingFiles
	)
	{
		$this->files = $files;
		$this->trainingFiles = $trainingFiles;
		parent::__construct($translator);
	}


	public function actionTraining($filename)
	{
		$session = $this->getSession('application');
		if (!$session->applicationId) {
			throw new BadRequestException("Unknown application id, missing or invalid token", Response::S404_NOT_FOUND);
		}

		$file = $this->trainingFiles->getFile($session->applicationId, $session->token, $filename);
		if (!$file) {
			throw new BadRequestException("No file {$filename} for application id {$session->applicationId}", Response::S404_NOT_FOUND);
		}

		$downloadId = $this->files->logDownload($file->fileId);
		$this->trainingFiles->logDownload($session->applicationId, $downloadId);
		$this->sendFile("{$file->dirName}/{$file->fileName}");
	}


	public function actionFile($filename)
	{
		throw new BadRequestException("Cannot download {$filename}", Response::S404_NOT_FOUND);
	}


	protected function sendFile($file)
	{
		$this->sendResponse(new FileResponse($file, null, finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file)));
	}


}
