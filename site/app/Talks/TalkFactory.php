<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks;

use Contributte\Translation\Translator;
use DateTime;
use MichalSpacekCz\Application\Locale\LocaleLinkGenerator;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Media\Exceptions\ContentTypeException;
use MichalSpacekCz\Media\VideoFactory;
use Nette\Application\UI\InvalidLinkException;
use Nette\Database\Row;

readonly class TalkFactory
{

	public function __construct(
		private TexyFormatter $texyFormatter,
		private VideoFactory $videoFactory,
		private Translator $translator,
		private LocaleLinkGenerator $localeLinkGenerator,
	) {
	}


	/**
	 * @throws ContentTypeException
	 * @throws InvalidLinkException
	 */
	public function createFromDatabaseRow(Row $row): Talk
	{
		assert(is_int($row->id));
		assert(is_int($row->localeId));
		assert(is_string($row->locale));
		assert($row->translationGroupId === null || is_int($row->translationGroupId));
		assert($row->action === null || is_string($row->action));
		assert($row->url === null || is_string($row->url));
		assert(is_string($row->title));
		assert(is_string($row->titleTexy));
		assert($row->description === null || is_string($row->description));
		assert($row->descriptionTexy === null || is_string($row->descriptionTexy));
		assert($row->date instanceof DateTime);
		assert($row->duration === null || is_int($row->duration));
		assert($row->href === null || is_string($row->href));
		assert(is_int($row->hasSlides));
		assert($row->slidesHref === null || is_string($row->slidesHref));
		assert($row->slidesEmbed === null || is_string($row->slidesEmbed));
		assert($row->slidesNote === null || is_string($row->slidesNote));
		assert($row->slidesNoteTexy === null || is_string($row->slidesNoteTexy));
		assert($row->videoEmbed === null || is_string($row->videoEmbed));
		assert(is_string($row->event));
		assert(is_string($row->eventTexy));
		assert($row->eventHref === null || is_string($row->eventHref));
		assert($row->ogImage === null || is_string($row->ogImage));
		assert($row->transcript === null || is_string($row->transcript));
		assert($row->transcriptTexy === null || is_string($row->transcriptTexy));
		assert($row->favorite === null || is_string($row->favorite));
		assert($row->slidesTalkId === null || is_int($row->slidesTalkId));
		assert($row->filenamesTalkId === null || is_int($row->filenamesTalkId));
		assert($row->supersededById === null || is_int($row->supersededById));
		assert($row->supersededByAction === null || is_string($row->supersededByAction));
		assert($row->supersededByTitle === null || is_string($row->supersededByTitle));
		assert(is_int($row->publishSlides));

		if ($this->translator->getDefaultLocale() !== $row->locale && $row->action !== null) {
			$links = $this->localeLinkGenerator->links('Www:Talks:talk', $this->localeLinkGenerator->defaultParams(['name' => $row->action]));
			$url = isset($links[$row->locale]) ? $links[$row->locale]->getUrl() : null;
		} else {
			$url = null;
		}
		return new Talk(
			$row->id,
			$row->localeId,
			$row->locale,
			$row->translationGroupId,
			$row->action,
			$url,
			$this->texyFormatter->format($row->title),
			$row->title,
			$row->description !== null ? $this->texyFormatter->formatBlock($row->description) : null,
			$row->description,
			$row->date,
			$row->duration,
			$row->href,
			(bool)$row->hasSlides,
			$row->slidesHref,
			$row->slidesEmbed,
			$row->slidesNote !== null ? $this->texyFormatter->formatBlock($row->slidesNote) : null,
			$row->slidesNote,
			$this->videoFactory->createFromDatabaseRow($row),
			$row->videoEmbed,
			$this->texyFormatter->format($row->event),
			$row->event,
			$row->eventHref,
			$row->ogImage,
			$row->transcript !== null ? $this->texyFormatter->formatBlock($row->transcript) : null,
			$row->transcript,
			$row->favorite,
			$row->slidesTalkId,
			$row->filenamesTalkId,
			$row->supersededById,
			$row->supersededByAction,
			$row->supersededByTitle,
			(bool)$row->publishSlides,
		);
	}

}
