<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Interviews;

use DateTime;
use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Interviews\Exceptions\InterviewDoesNotExistException;
use MichalSpacekCz\Media\Exceptions\ContentTypeException;
use MichalSpacekCz\Media\VideoFactory;
use Nette\Database\Explorer;
use Nette\Database\Row;

final readonly class Interviews
{

	public function __construct(
		private Explorer $database,
		private TypedDatabase $typedDatabase,
		private VideoFactory $videoFactory,
		private TexyFormatter $texyFormatter,
	) {
	}


	/**
	 * @return list<Interview>
	 */
	public function getAll(?int $limit = null): array
	{
		$query = 'SELECT
				id_interview AS id,
				action,
				title,
				description,
				date,
				href,
				audio_href AS audioHref,
				audio_embed AS audioEmbed,
				video_href AS videoHref,
				video_thumbnail AS videoThumbnail,
				video_thumbnail_alternative AS videoThumbnailAlternative,
				video_embed AS videoEmbed,
				source_name AS sourceName,
				source_href AS sourceHref
			FROM interviews
			ORDER BY date DESC
			LIMIT ?';

		$result = $this->typedDatabase->fetchAll($query, $limit ?? PHP_INT_MAX);
		$interviews = [];
		foreach ($result as $row) {
			$interviews[] = $this->createFromDatabaseRow($row);
		}
		return $interviews;
	}


	/**
	 * @throws InterviewDoesNotExistException
	 */
	public function get(string $name): Interview
	{
		$result = $this->database->fetch(
			'SELECT
				id_interview AS id,
				action,
				title,
				description,
				date,
				href,
				audio_href AS audioHref,
				audio_embed AS audioEmbed,
				video_href AS videoHref,
				video_thumbnail AS videoThumbnail,
				video_thumbnail_alternative AS videoThumbnailAlternative,
				video_embed AS videoEmbed,
				source_name AS sourceName,
				source_href AS sourceHref
			FROM interviews
			WHERE action = ?',
			$name,
		);

		if ($result === null) {
			throw new InterviewDoesNotExistException(name: $name);
		}
		return $this->createFromDatabaseRow($result);
	}


	/**
	 * @throws InterviewDoesNotExistException
	 */
	public function getById(int $id): Interview
	{
		$result = $this->database->fetch(
			'SELECT
				id_interview AS id,
				action,
				title,
				description,
				date,
				href,
				audio_href AS audioHref,
				audio_embed AS audioEmbed,
				video_href AS videoHref,
				video_thumbnail AS videoThumbnail,
				video_thumbnail_alternative AS videoThumbnailAlternative,
				video_embed AS videoEmbed,
				source_name AS sourceName,
				source_href AS sourceHref
			FROM interviews
			WHERE id_interview = ?',
			$id,
		);

		if ($result === null) {
			throw new InterviewDoesNotExistException(id: $id);
		}
		return $this->createFromDatabaseRow($result);
	}


	/**
	 * @throws ContentTypeException
	 */
	private function createFromDatabaseRow(Row $row): Interview
	{
		assert(is_int($row->id));
		assert(is_string($row->action));
		assert(is_string($row->title));
		assert($row->description === null || is_string($row->description));
		assert($row->date instanceof DateTime);
		assert(is_string($row->href));
		assert($row->audioHref === null || is_string($row->audioHref));
		assert($row->audioEmbed === null || is_string($row->audioEmbed));
		assert($row->videoEmbed === null || is_string($row->videoEmbed));
		assert(is_string($row->sourceName));
		assert(is_string($row->sourceHref));

		return new Interview(
			$row->id,
			$row->action,
			$row->title,
			$row->description,
			$row->description !== null ? $this->texyFormatter->formatBlock($row->description) : null,
			$row->date,
			$row->href,
			$row->audioHref,
			$row->audioEmbed,
			$this->videoFactory->createFromDatabaseRow($row),
			$row->videoEmbed,
			$row->sourceName,
			$row->sourceHref,
		);
	}


	public function update(
		int $id,
		string $action,
		string $title,
		string $description,
		string $date,
		string $href,
		string $audioHref,
		string $audioEmbed,
		string $videoHref,
		?string $videoThumbnail,
		?string $videoThumbnailAlternative,
		string $videoEmbed,
		string $sourceName,
		string $sourceHref,
	): void {
		$this->database->query(
			'UPDATE interviews SET ? WHERE id_interview = ?',
			[
				'action' => $action,
				'title' => $title,
				'description' => $description === '' ? null : $description,
				'date' => new DateTime($date),
				'href' => $href,
				'audio_href' => $audioHref === '' ? null : $audioHref,
				'audio_embed' => $audioEmbed === '' ? null : $audioEmbed,
				'video_href' => $videoHref === '' ? null : $videoHref,
				'video_thumbnail' => $videoThumbnail,
				'video_thumbnail_alternative' => $videoThumbnailAlternative,
				'video_embed' => $videoEmbed === '' ? null : $videoEmbed,
				'source_name' => $sourceName,
				'source_href' => $sourceHref,
			],
			$id,
		);
	}


	public function add(
		string $action,
		string $title,
		string $description,
		string $date,
		string $href,
		string $audioHref,
		string $audioEmbed,
		string $videoHref,
		?string $videoThumbnail,
		?string $videoThumbnailAlternative,
		string $videoEmbed,
		string $sourceName,
		string $sourceHref,
	): int {
		$this->database->query(
			'INSERT INTO interviews',
			[
				'action' => $action,
				'title' => $title,
				'description' => $description === '' ? null : $description,
				'date' => new DateTime($date),
				'href' => $href,
				'audio_href' => $audioHref === '' ? null : $audioHref,
				'audio_embed' => $audioEmbed === '' ? null : $audioEmbed,
				'video_href' => $videoHref === '' ? null : $videoHref,
				'video_thumbnail' => $videoThumbnail,
				'video_thumbnail_alternative' => $videoThumbnailAlternative,
				'video_embed' => $videoEmbed === '' ? null : $videoEmbed,
				'source_name' => $sourceName,
				'source_href' => $sourceHref,
			],
		);
		return (int)$this->database->getInsertId();
	}

}
