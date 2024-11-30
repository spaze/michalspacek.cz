<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Application\LinkGenerator;
use MichalSpacekCz\Application\Locale\Locales;
use MichalSpacekCz\Form\Controls\TrainingControlsFactory;
use MichalSpacekCz\Media\VideoThumbnails;
use MichalSpacekCz\Talks\Talk;
use MichalSpacekCz\Talks\Talks;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Form;
use Nette\Utils\Html;
use Nette\Utils\Strings;

readonly class TalkFormFactory
{

	public function __construct(
		private FormFactory $factory,
		private TrainingControlsFactory $trainingControlsFactory,
		private Talks $talks,
		private LinkGenerator $linkGenerator,
		private VideoThumbnails $videoThumbnails,
		private Locales $locales,
	) {
	}


	/**
	 * @param callable(Html): void $onSuccess
	 */
	public function create(callable $onSuccess, ?Talk $talk = null): UiForm
	{
		$form = $this->factory->create();
		$allTalks = $this->getAllTalksExcept($talk ? (string)$talk->getAction() : null);

		$form->addInteger('translationGroup', 'Skupina překladů:')
			->setRequired(false);
		$form->addSelect('locale', 'Jazyk:', $this->locales->getAllLocales())
			->setRequired('Zadejte prosím jazyk')
			->setPrompt('- vyberte -');
		$form->addText('action', 'Akce:')
			->setRequired(false)
			->addRule(Form::MaxLength, 'Maximální délka akce je %d znaků', 200);
		$form->addText('title', 'Název:')
			->setRequired('Zadejte prosím název')
			->addRule(Form::MaxLength, 'Maximální délka názvu je %d znaků', 200);
		$form->addTextArea('description', 'Popis:')
			->setRequired(false)
			->addRule(Form::MaxLength, 'Maximální délka popisu je %d znaků', 65535);
		$this->trainingControlsFactory->addDate(
			$form->addText('date', 'Datum:'),
			true,
			'YYYY-MM-DD HH:MM nebo DD.MM.YYYY HH:MM',
			'(\d{4}-\d{1,2}-\d{1,2} \d{1,2}:\d{2})|(\d{1,2}\.\d{1,2}\.\d{4} \d{1,2}:\d{2})',
		);
		$form->addText('href', 'Odkaz na přednášku:')
			->setRequired(false)
			->addRule(Form::MaxLength, 'Maximální délka odkazu na přednášku je %d znaků', 200);
		$form->addText('duration', 'Délka:')
			->setHtmlType('number');
		$form->addSelect('slidesTalk', 'Použít slajdy z:', $allTalks)
			->setPrompt('Vyberte prosím přednášku, ze které se použijí slajdy');
		$form->addSelect('filenamesTalk', 'Soubory pro slajdy z:', $allTalks)
			->setPrompt('Vyberte prosím přednášku, ze které se použijí soubory pro slajdy');
		$form->addText('slidesHref', 'Odkaz na slajdy:')
			->setRequired(false)
			->addRule(Form::MaxLength, 'Maximální délka odkazu na slajdy je %d znaků', 200);
		$form->addText('slidesEmbed', 'Embed odkaz na slajdy:')
			->setRequired(false)
			->addRule(Form::MaxLength, 'Maximální délka embed odkazu na slajdy je %d znaků', 200);
		$form->addTextArea('slidesNote', 'Poznámka ke slajdům:')
			->setRequired(false)
			->addRule(Form::MaxLength, 'Maximální délka poznámek je %d znaků', 65535);
		$form->addText('videoHref', 'Odkaz na video:')
			->setRequired(false)
			->addRule(Form::MaxLength, 'Maximální délka odkazu na video je %d znaků', 200);
		$videoThumbnailFormFields = $this->videoThumbnails->addFormFields($form, $talk?->getVideo()->getThumbnailFilename() !== null, $talk?->getVideo()->getThumbnailAlternativeContentType() !== null);
		$form->addText('videoEmbed', 'Embed odkaz na video:')
			->setRequired(false)
			->addRule(Form::MaxLength, 'Maximální délka embed odkazu na video je %d znaků', 200);
		$form->addText('event', 'Událost:')
			->setRequired('Zadejte prosím událost')
			->addRule(Form::MaxLength, 'Maximální délka události je %d znaků', 200);
		$form->addText('eventHref', 'Odkaz na událost:')
			->setRequired(false)
			->addRule(Form::MaxLength, 'Maximální délka odkazu na událost je %d znaků', 200);
		$form->addText('ogImage', 'Odkaz na obrázek:')
			->setRequired(false)
			->addRule(Form::MaxLength, 'Maximální délka odkazu na obrázek je %d znaků', 200);
		$form->addTextArea('transcript', 'Přepis:')
			->setRequired(false)
			->addRule(Form::MaxLength, 'Maximální délka přepisu je %d znaků', 65535);
		$form->addTextArea('favorite', 'Popis pro oblíbené:')
			->setRequired(false)
			->addRule(Form::MaxLength, 'Maximální délka popisu pro oblíbené je %d znaků', 65535);
		$form->addSelect('supersededBy', 'Nahrazeno přednáškou:', $allTalks)
			->setPrompt('Vyberte prosím přednášku, kterou se tato nahradí');
		$form->addCheckbox('publishSlides', 'Publikovat slajdy:');
		$submit = $form->addSubmit('submit', 'Přidat');

		if ($talk) {
			$this->setTalk($form, $talk, $submit);
		}

		$form->onSuccess[] = function (UiForm $form) use ($talk, $onSuccess, $videoThumbnailFormFields): void {
			$values = $form->getFormValues();
			$videoThumbnailBasename = $this->videoThumbnails->getUploadedMainFileBasename($values);
			$videoThumbnailBasenameAlternative = $this->videoThumbnails->getUploadedAlternativeFileBasename($values);
			if ($talk) {
				$removeVideoThumbnail = $videoThumbnailFormFields->hasVideoThumbnail() && $values->removeVideoThumbnail;
				$removeVideoThumbnailAlternative = $videoThumbnailFormFields->hasAlternativeVideoThumbnail() && $values->removeVideoThumbnailAlternative;
				$thumbnailFilename = $talk->getVideo()->getThumbnailFilename();
				$thumbnailAlternativeFilename = $talk->getVideo()->getThumbnailAlternativeFilename();
				$this->talks->update(
					$talk->getId(),
					$values->locale,
					$values->translationGroup,
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
					$values->slidesNote,
					$values->videoHref,
					$videoThumbnailBasename ?? ($removeVideoThumbnail ? null : $thumbnailFilename),
					$videoThumbnailBasenameAlternative ?? ($removeVideoThumbnailAlternative ? null : $thumbnailAlternativeFilename),
					$values->videoEmbed,
					$values->event,
					$values->eventHref,
					$values->ogImage,
					$values->transcript,
					$values->favorite,
					$values->supersededBy,
					$values->publishSlides,
				);
				$this->videoThumbnails->saveVideoThumbnailFiles($talk->getId(), $values);
				if ($removeVideoThumbnail && $thumbnailFilename !== null) {
					$this->videoThumbnails->deleteFile($talk->getId(), $thumbnailFilename);
				}
				if ($removeVideoThumbnailAlternative && $thumbnailAlternativeFilename !== null) {
					$this->videoThumbnails->deleteFile($talk->getId(), $thumbnailAlternativeFilename);
				}
				$message = Html::el()->setText('Přednáška upravena ');
			} else {
				$talkId = $this->talks->add(
					$values->locale,
					$values->translationGroup,
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
					$values->slidesNote,
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
		return $form;
	}


	public function setTalk(UiForm $form, Talk $talk, SubmitButton $submit): void
	{
		$values = [
			'action' => $talk->getAction(),
			'locale' => $talk->getLocaleId(),
			'translationGroup' => $talk->getTranslationGroupId(),
			'title' => $talk->getTitleTexy(),
			'description' => $talk->getDescriptionTexy(),
			'date' => $talk->getDate()->format('Y-m-d H:i'),
			'href' => $talk->getHref(),
			'duration' => $talk->getDuration(),
			'slidesTalk' => $talk->getSlidesTalkId(),
			'filenamesTalk' => $talk->getFilenamesTalkId(),
			'slidesHref' => $talk->getSlidesHref(),
			'slidesEmbed' => $talk->getSlidesEmbed(),
			'slidesNote' => $talk->getSlidesNoteTexy(),
			'videoHref' => $talk->getVideo()->getVideoHref(),
			'videoEmbed' => $talk->getVideoEmbed(),
			'event' => $talk->getEventTexy(),
			'eventHref' => $talk->getEventHref(),
			'ogImage' => $talk->getOgImage(),
			'transcript' => $talk->getTranscriptTexy(),
			'favorite' => $talk->getFavorite(),
			'supersededBy' => $talk->getSupersededById(),
			'publishSlides' => $talk->isPublishSlides(),
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
			if ($talkAction !== $talk->getAction()) {
				$title = Strings::truncate($talk->getTitleTexy(), 40);
				$event = Strings::truncate(strip_tags($talk->getEvent()->render()), 30);
				$allTalks[$talk->getId()] = sprintf('%s (%s, %s)', $title, $talk->getDate()->format('j. n. Y'), $event);
			}
		}
		return $allTalks;
	}

}
