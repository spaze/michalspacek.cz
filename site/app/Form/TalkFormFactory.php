<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Form\Controls\TrainingControlsFactory;
use MichalSpacekCz\Media\VideoThumbnails;
use MichalSpacekCz\Talks\Talks;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\Form;
use Nette\Database\Row;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\Html;
use Nette\Utils\Strings;
use stdClass;

class TalkFormFactory
{

	public function __construct(
		private readonly FormFactory $factory,
		private readonly TrainingControlsFactory $trainingControlsFactory,
		private readonly Talks $talks,
		private readonly LinkGenerator $linkGenerator,
		private readonly VideoThumbnails $videoThumbnails,
	) {
	}


	/**
	 * @param callable(Html): void $onSuccess
	 * @param Row|null $talk
	 * @return Form
	 */
	public function create(callable $onSuccess, ?Row $talk = null): Form
	{
		$form = $this->factory->create();
		$allTalks = $this->getAllTalksExcept($talk ? (string)$talk->action : null);

		$form->addText('action', 'Akce:')
			->setRequired(false)
			->addRule($form::MAX_LENGTH, 'Maximální délka akce je %d znaků', 200);
		$form->addText('title', 'Název:')
			->setRequired('Zadejte prosím název')
			->addRule($form::MAX_LENGTH, 'Maximální délka názvu je %d znaků', 200);
		$form->addTextArea('description', 'Popis:')
			->setRequired(false)
			->addRule($form::MAX_LENGTH, 'Maximální délka popisu je %d znaků', 65535);
		$this->trainingControlsFactory->addDate(
			$form,
			'date',
			'Datum:',
			true,
			'YYYY-MM-DD HH:MM nebo DD.MM.YYYY HH:MM',
			'(\d{4}-\d{1,2}-\d{1,2} \d{1,2}:\d{2})|(\d{1,2}\.\d{1,2}\.\d{4} \d{1,2}:\d{2})',
		);
		$form->addText('href', 'Odkaz na přednášku:')
			->setRequired(false)
			->addRule($form::MAX_LENGTH, 'Maximální délka odkazu na přednášku je %d znaků', 200);
		$form->addText('duration', 'Délka:')
			->setHtmlType('number');
		$form->addSelect('slidesTalk', 'Použít slajdy z:', $allTalks)
			->setPrompt('Vyberte prosím přednášku, ze které se použijí slajdy');
		$form->addSelect('filenamesTalk', 'Soubory pro slajdy z:', $allTalks)
			->setPrompt('Vyberte prosím přednášku, ze které se použijí soubory pro slajdy');
		$form->addText('slidesHref', 'Odkaz na slajdy:')
			->setRequired(false)
			->addRule($form::MAX_LENGTH, 'Maximální délka odkazu na slajdy je %d znaků', 200);
		$form->addText('slidesEmbed', 'Embed odkaz na slajdy:')
			->setRequired(false)
			->addRule($form::MAX_LENGTH, 'Maximální délka embed odkazu na slajdy je %d znaků', 200);
		$form->addText('videoHref', 'Odkaz na video:')
			->setRequired(false)
			->addRule($form::MAX_LENGTH, 'Maximální délka odkazu na video je %d znaků', 200);
		$videoThumbnailFormFields = $this->videoThumbnails->addFormFields($form, $talk?->videoThumbnail !== null, $talk?->videoThumbnailAlternative !== null);
		$form->addText('videoEmbed', 'Embed odkaz na video:')
			->setRequired(false)
			->addRule($form::MAX_LENGTH, 'Maximální délka embed odkazu na video je %d znaků', 200);
		$form->addText('event', 'Událost:')
			->setRequired('Zadejte prosím událost')
			->addRule($form::MAX_LENGTH, 'Maximální délka události je %d znaků', 200);
		$form->addText('eventHref', 'Odkaz na událost:')
			->setRequired(false)
			->addRule($form::MAX_LENGTH, 'Maximální délka odkazu na událost je %d znaků', 200);
		$form->addText('ogImage', 'Odkaz na obrázek:')
			->setRequired(false)
			->addRule($form::MAX_LENGTH, 'Maximální délka odkazu na obrázek je %d znaků', 200);
		$form->addTextArea('transcript', 'Přepis:')
			->setRequired(false)
			->addRule($form::MAX_LENGTH, 'Maximální délka přepisu je %d znaků', 65535);
		$form->addTextArea('favorite', 'Popis pro oblíbené:')
			->setRequired(false)
			->addRule($form::MAX_LENGTH, 'Maximální délka popisu pro oblíbené je %d znaků', 65535);
		$form->addSelect('supersededBy', 'Nahrazeno přednáškou:', $allTalks)
			->setPrompt('Vyberte prosím přednášku, kterou se tato nahradí');
		$form->addCheckbox('publishSlides', 'Publikovat slajdy:');
		$submit = $form->addSubmit('submit', 'Přidat');

		if ($talk) {
			$this->setTalk($form, $talk, $submit);
		}

		$form->onSuccess[] = function (Form $form, stdClass $values) use ($talk, $onSuccess): void {
			$videoThumbnailBasename = $this->videoThumbnails->getUploadedMainFileBasename($values);
			$videoThumbnailBasenameAlternative = $this->videoThumbnails->getUploadedAlternativeFileBasename($values);
			if ($talk) {
				$removeVideoThumbnail = $values->removeVideoThumbnail ?? false;
				$removeVideoThumbnailAlternative = $values->removeVideoThumbnailAlternative ?? false;
				$this->talks->update(
					$talk->talkId,
					$values->action,
					$values->title,
					$values->description,
					$values->date,
					(int)$values->duration,
					$values->href,
					$values->slidesTalk,
					$values->filenamesTalk,
					$values->slidesHref,
					$values->slidesEmbed,
					$values->videoHref,
					$videoThumbnailBasename ?? ($removeVideoThumbnail ? null : $talk->videoThumbnail),
					$videoThumbnailBasenameAlternative ?? ($removeVideoThumbnailAlternative ? null : $talk->videoThumbnailAlternative),
					$values->videoEmbed,
					$values->event,
					$values->eventHref,
					$values->ogImage,
					$values->transcript,
					$values->favorite,
					$values->supersededBy,
					$values->publishSlides,
				);
				$this->videoThumbnails->saveVideoThumbnailFiles($talk->talkId, $values);
				if ($removeVideoThumbnail) {
					$this->videoThumbnails->deleteFile($talk->talkId, $talk->videoThumbnail);
				}
				if ($removeVideoThumbnailAlternative) {
					$this->videoThumbnails->deleteFile($talk->talkId, $talk->videoThumbnailAlternative);
				}
				$message = Html::el()->setText('Přednáška upravena ');
			} else {
				$talkId = $this->talks->add(
					$values->action,
					$values->title,
					$values->description,
					$values->date,
					(int)$values->duration,
					$values->href,
					$values->slidesTalk,
					$values->filenamesTalk,
					$values->slidesHref,
					$values->slidesEmbed,
					$values->videoHref,
					$videoThumbnailBasename,
					$videoThumbnailBasenameAlternative,
					$values->videoEmbed,
					$values->event,
					$values->eventHref,
					$values->ogImage,
					$values->transcript,
					$values->favorite,
					$values->supersededBy,
					$values->publishSlides,
				);
				$this->videoThumbnails->saveVideoThumbnailFiles($talkId, $values);
				$message = Html::el()->setText('Přednáška přidána ');
			}
			$message->addHtml(Html::el('a')->href($this->linkGenerator->link('Www:Talks:talk', [$values->action]))->setText('Zobrazit'));
			$onSuccess($message);
		};

		$this->videoThumbnails->addOnValidateUploads($form, $videoThumbnailFormFields);

		return $form;
	}


	/**
	 * @param Form $form
	 * @param Row<mixed> $talk
	 * @param SubmitButton $submit
	 * @return void
	 */
	public function setTalk(Form $form, Row $talk, SubmitButton $submit): void
	{
		$values = [
			'action' => $talk->action,
			'title' => $talk->titleTexy,
			'description' => $talk->descriptionTexy,
			'date' => $talk->date->format('Y-m-d H:i'),
			'href' => $talk->href,
			'duration' => $talk->duration,
			'slidesTalk' => $talk->slidesTalkId,
			'filenamesTalk' => $talk->filenamesTalkId,
			'slidesHref' => $talk->slidesHref,
			'slidesEmbed' => $talk->slidesEmbed,
			'videoHref' => $talk->videoHref,
			'videoEmbed' => $talk->videoEmbed,
			'event' => $talk->eventTexy,
			'eventHref' => $talk->eventHref,
			'ogImage' => $talk->ogImage,
			'transcript' => $talk->transcriptTexy,
			'favorite' => $talk->favorite,
			'supersededBy' => $talk->supersededById,
			'publishSlides' => $talk->publishSlides,
		];
		$form->setDefaults($values);
		$submit->caption = 'Upravit';
	}


	/**
	 * @param string|null $talkAction
	 * @return array<int, string>
	 */
	private function getAllTalksExcept(?string $talkAction): array
	{
		$allTalks = [];
		foreach ($this->talks->getAll() as $talk) {
			if ($talkAction !== $talk->action) {
				$title = Strings::truncate($talk->titleTexy, 40);
				$event = Strings::truncate((string)$talk->event, 30);
				$allTalks[(int)$talk->talkId] = sprintf('%s (%s, %s)', $title, $talk->date->format('j. n. Y'), $event);
			}
		}
		return $allTalks;
	}

}
