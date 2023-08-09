<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks;

use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Media\Exceptions\ContentTypeException;
use MichalSpacekCz\Media\VideoFactory;
use Nette\Database\Row;

class TalkFactory
{

	public function __construct(
		private readonly TexyFormatter $texyFormatter,
		private readonly VideoFactory $videoFactory,
	) {
	}


	/**
	 * @throws ContentTypeException
	 */
	public function createFromDatabaseRow(Row $row): Talk
	{
		return new Talk(
			$row->id,
			$row->localeId,
			$row->action,
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
