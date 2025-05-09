<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Form\Controls\TrainingControlsFactory;
use MichalSpacekCz\Interviews\Interview;
use MichalSpacekCz\Interviews\Interviews;
use MichalSpacekCz\Media\VideoThumbnails;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Form;
use Nette\Http\FileUpload;

final readonly class InterviewFormFactory
{

	public function __construct(
		private FormFactory $factory,
		private TrainingControlsFactory $trainingControlsFactory,
		private Interviews $interviews,
		private VideoThumbnails $videoThumbnails,
	) {
	}


	/**
	 * @param callable(): void $onSuccess
	 */
	public function create(callable $onSuccess, ?Interview $interview = null): UiForm
	{
		$form = $this->factory->create();
		$form->addText('action', 'Akce:')
			->setRequired('Zadejte prosím akci')
			->addRule(Form::MaxLength, 'Maximální délka akce je %d znaků', 200);
		$form->addText('title', 'Název:')
			->setRequired('Zadejte prosím název')
			->addRule(Form::MaxLength, 'Maximální délka názvu je %d znaků', 200);
		$form->addTextArea('description', 'Popis:')
			->setRequired(false);
		$this->trainingControlsFactory->addDate(
			$form->addText('date', 'Datum:'),
			true,
			'YYYY-MM-DD HH:MM nebo DD.MM.YYYY HH:MM',
			'(\d{4}-\d{1,2}-\d{1,2} \d{1,2}:\d{2})|(\d{1,2}\.\d{1,2}\.\d{4} \d{1,2}:\d{2})',
		);
		$form->addText('href', 'Odkaz na rozhovor:')
			->setRequired('Zadejte prosím odkaz na rozhovor')
			->addRule(Form::MaxLength, 'Maximální délka odkazu na rozhovor je %d znaků', 200);
		$form->addText('audioHref', 'Odkaz na audio:')
			->setRequired(false)
			->addRule(Form::MaxLength, 'Maximální délka odkazu na audio je %d znaků', 200);
		$form->addText('audioEmbed', 'Embed odkaz na audio:')
			->setRequired(false)
			->addRule(Form::MaxLength, 'Maximální délka embed odkazu na audio je %d znaků', 200);
		$form->addText('videoHref', 'Odkaz na video:')
			->setRequired(false)
			->addRule(Form::MaxLength, 'Maximální délka odkazu na video je %d znaků', 200);
		$videoThumbnailFormFields = $this->videoThumbnails->addFormFields($form, $interview?->getVideo()->getThumbnailFilename() !== null, $interview?->getVideo()->getThumbnailAlternativeFilename() !== null);
		$form->addText('videoEmbed', 'Embed odkaz na video:')
			->setRequired(false)
			->addRule(Form::MaxLength, 'Maximální délka embed odkazu na video je %d znaků', 200);
		$form->addText('sourceName', 'Název zdroje:')
			->setRequired('Zadejte prosím název zdroje')
			->addRule(Form::MaxLength, 'Maximální délka názvu zdroje je %d znaků', 200);
		$form->addText('sourceHref', 'Odkaz na zdroj:')
			->setRequired('Zadejte prosím odkaz na zdroj')
			->addRule(Form::MaxLength, 'Maximální délka odkazu na zdroj je %d znaků', 200);
		$submit = $form->addSubmit('submit', 'Přidat');
		if ($interview !== null) {
			$this->setInterview($form, $interview, $submit);
		}

		$form->onSuccess[] = function (UiForm $form) use ($interview, $onSuccess, $videoThumbnailFormFields): void {
			$values = $form->getFormValues();
			assert($values->videoThumbnail instanceof FileUpload);
			assert($values->videoThumbnailAlternative instanceof FileUpload);
			assert(is_string($values->action));
			assert(is_string($values->title));
			assert(is_string($values->description));
			assert(is_string($values->date));
			assert(is_string($values->href));
			assert(is_string($values->audioHref));
			assert(is_string($values->audioEmbed));
			assert(is_string($values->videoHref));
			assert(is_string($values->videoEmbed));
			assert(is_string($values->sourceName));
			assert(is_string($values->sourceHref));
			$videoThumbnailBasename = $this->videoThumbnails->getUploadedMainFileBasename($values->videoThumbnail);
			$videoThumbnailBasenameAlternative = $this->videoThumbnails->getUploadedAlternativeFileBasename($values->videoThumbnailAlternative);
			if ($interview !== null) {
				$removeVideoThumbnail = $videoThumbnailFormFields->hasVideoThumbnail() && $values->removeVideoThumbnail;
				$removeVideoThumbnailAlternative = $videoThumbnailFormFields->hasAlternativeVideoThumbnail() && $values->removeVideoThumbnailAlternative;
				$thumbnailFilename = $interview->getVideo()->getThumbnailFilename();
				$thumbnailAlternativeFilename = $interview->getVideo()->getThumbnailAlternativeFilename();
				$this->interviews->update(
					$interview->getId(),
					$values->action,
					$values->title,
					$values->description,
					$values->date,
					$values->href,
					$values->audioHref,
					$values->audioEmbed,
					$values->videoHref,
					$videoThumbnailBasename ?? ($removeVideoThumbnail ? null : $thumbnailFilename),
					$videoThumbnailBasenameAlternative ?? ($removeVideoThumbnailAlternative ? null : $thumbnailAlternativeFilename),
					$values->videoEmbed,
					$values->sourceName,
					$values->sourceHref,
				);
				$this->videoThumbnails->saveVideoThumbnailFiles($interview->getId(), $values->videoThumbnail, $values->videoThumbnailAlternative);
				if ($removeVideoThumbnail && $thumbnailFilename !== null) {
					$this->videoThumbnails->deleteFile($interview->getId(), $thumbnailFilename);
				}
				if ($removeVideoThumbnailAlternative && $thumbnailAlternativeFilename !== null) {
					$this->videoThumbnails->deleteFile($interview->getId(), $thumbnailAlternativeFilename);
				}
			} else {
				$interviewId = $this->interviews->add(
					$values->action,
					$values->title,
					$values->description,
					$values->date,
					$values->href,
					$values->audioHref,
					$values->audioEmbed,
					$values->videoHref,
					$videoThumbnailBasename,
					$videoThumbnailBasenameAlternative,
					$values->videoEmbed,
					$values->sourceName,
					$values->sourceHref,
				);
				$this->videoThumbnails->saveVideoThumbnailFiles($interviewId, $values->videoThumbnail, $values->videoThumbnailAlternative);
			}
			$onSuccess();
		};
		return $form;
	}


	public function setInterview(UiForm $form, Interview $interview, SubmitButton $submit): void
	{
		$values = [
			'action' => $interview->getAction(),
			'title' => $interview->getTitle(),
			'description' => $interview->getDescriptionTexy(),
			'date' => $interview->getDate()->format('Y-m-d H:i'),
			'href' => $interview->getHref(),
			'audioHref' => $interview->getAudioHref(),
			'audioEmbed' => $interview->getAudioEmbed(),
			'videoHref' => $interview->getVideo()->getVideoHref(),
			'videoEmbed' => $interview->getVideoEmbed(),
			'sourceName' => $interview->getSourceName(),
			'sourceHref' => $interview->getSourceHref(),
		];
		$form->setDefaults($values);
		$submit->caption = 'Upravit';
	}

}
