<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Files;

use finfo;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Training\Applications\TrainingApplication;
use MichalSpacekCz\Training\Applications\TrainingApplications;
use MichalSpacekCz\Training\Applications\TrainingApplicationSessionSection;
use MichalSpacekCz\Training\Exceptions\TrainingApplicationDoesNotExistException;
use Nette\Application\Application;
use Nette\Application\BadRequestException;
use Nette\Application\Responses\FileResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\Session;
use ParagonIE\Halite\Alerts\HaliteAlert;
use SodiumException;

readonly class TrainingFilesDownload
{

	public function __construct(
		private Application $application,
		private TrainingApplications $trainingApplications,
		private Session $sessionHandler,
		private TrainingFiles $trainingFiles,
	) {
	}


	/**
	 * @throws HaliteAlert
	 * @throws SodiumException
	 */
	public function start(string $trainingAction, ?string $token): TrainingApplication
	{
		$session = $this->getSessionSection();

		if ($token !== null) {
			$application = $this->trainingApplications->getApplicationByToken($token);
			$session->setValues($token, $application);
			$presenter = $this->application->getPresenter();
			if (!$presenter instanceof Presenter) {
				throw new ShouldNotHappenException(sprintf("The presenter should be a '%s' but it's a %s", Presenter::class, get_debug_type($presenter)));
			}
			$presenter->redirect('files', $application?->getTrainingAction() ?? $trainingAction);
		}

		if (!$session->isComplete()) {
			throw new BadRequestException('Unknown application id, missing or invalid token');
		}

		try {
			return $this->trainingApplications->getApplicationById($session->getApplicationId());
		} catch (TrainingApplicationDoesNotExistException $e) {
			throw new BadRequestException($e->getMessage(), previous: $e);
		}
	}


	public function getFileResponse(string $filename): FileResponse
	{
		$session = $this->getSessionSection();
		if (!$session->isComplete()) {
			throw new BadRequestException('Unknown application id, missing or invalid token');
		}

		$applicationId = $session->getApplicationId();
		$file = $this->trainingFiles->getFile($applicationId, $session->getToken(), $filename);
		if (!$file) {
			throw new BadRequestException(sprintf('No file %s for application id %s', $filename, $applicationId));
		}
		$pathname = $file->getFileInfo()->getPathname();
		$mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($pathname);
		return new FileResponse($pathname, null, $mimeType !== false ? $mimeType : null);
	}


	private function getSessionSection(): TrainingFilesSessionSection
	{
		$session = $this->sessionHandler->getSection('training', TrainingFilesSessionSection::class);
		if (!$session instanceof TrainingFilesSessionSection) {
			throw new ShouldNotHappenException(sprintf('Session section type is %s, but should be %s', get_debug_type($session), TrainingApplicationSessionSection::class));
		}
		return $session;
	}

}
