<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use DateTimeInterface;
use MichalSpacekCz\Training\Files\TrainingFiles;
use Nette\Application\UI\Form;
use stdClass;

class TrainingFileFormFactory
{

	public function __construct(
		private FormFactory $factory,
		private TrainingFiles $trainingFiles,
	) {
	}


	/**
	 * @param callable(?string): never $onSuccess
	 * @param DateTimeInterface $trainingStart
	 * @param array<int, int> $applicationIdsAllowedFiles
	 * @return Form
	 */
	public function create(callable $onSuccess, DateTimeInterface $trainingStart, array $applicationIdsAllowedFiles): Form
	{
		$form = $this->factory->create();
		$form->addUpload('file', 'Soubor:');
		$form->addSubmit('submit', 'Přidat');
		$form->onSuccess[] = function (Form $form, stdClass $values) use ($onSuccess, $trainingStart, $applicationIdsAllowedFiles): void {
			if ($values->file->isOk()) {
				$filename = $this->trainingFiles->addFile($trainingStart, $values->file, $applicationIdsAllowedFiles);
			} else {
				$filename = null;
			}
			$onSuccess($filename);
		};
		return $form;
	}

}
