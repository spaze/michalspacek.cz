<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks;

use Nette\Database\Row;

final class TalkMetadataFactory
{

	public function createFromDatabaseRow(Row $row): TalkMetadata
	{
		assert(is_int($row->id));
		assert($row->slidesTalkId === null || is_int($row->slidesTalkId));
		assert(is_int($row->publishSlides));
		return new TalkMetadata(
			$row->id,
			$row->slidesTalkId,
			(bool)$row->publishSlides,
		);
	}

}
