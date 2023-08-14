<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks;

use Contributte\Translation\Translator;
use MichalSpacekCz\Application\LocaleLinkGeneratorInterface;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Media\Exceptions\ContentTypeException;
use MichalSpacekCz\Media\VideoFactory;
use Nette\Application\UI\InvalidLinkException;
use Nette\Database\Row;

class TalkFactory
{

	public function __construct(
		private readonly TexyFormatter $texyFormatter,
		private readonly VideoFactory $videoFactory,
		private readonly Translator $translator,
		private readonly LocaleLinkGeneratorInterface $localeLinkGenerator,
	) {
	}


	/**
	 * @throws ContentTypeException
	 * @throws InvalidLinkException
	 */
	public function createFromDatabaseRow(Row $row): Talk
	{
		if ($this->translator->getDefaultLocale() !== $row->locale && $row->action) {
			$links = $this->localeLinkGenerator->links('Www:Talks:talk', $this->localeLinkGenerator->defaultParams(['name' => $row->action]));
			$url = isset($links[$row->locale]) ? $links[$row->locale]->getUrl() : null;
		}
		return new Talk(
			$row->id,
			$row->localeId,
			$row->locale,
			$row->translationGroupId,
			$row->action,
			$url ?? null,
			$this->texyFormatter->format($row->title),
			$row->title,
			$row->description ? $this->texyFormatter->formatBlock($row->description) : null,
			$row->description,
			$row->date,
			$row->duration,
			$row->href,
			(bool)$row->hasSlides,
			$row->slidesHref,
			$row->slidesEmbed,
			$this->videoFactory->createFromDatabaseRow($row),
			$row->videoEmbed,
			$this->texyFormatter->format($row->event),
			$row->event,
			$row->eventHref,
			$row->ogImage,
			$row->transcript ? $this->texyFormatter->formatBlock($row->transcript) : null,
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
