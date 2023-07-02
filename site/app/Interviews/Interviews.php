<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Interviews;

use DateTime;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Interviews\Exceptions\InterviewDoesNotExistException;
use MichalSpacekCz\Media\Resources\InterviewMediaResources;
use MichalSpacekCz\ShouldNotHappenException;
use Nette\Database\Explorer;
use Nette\Database\Row;

class Interviews
{

	public function __construct(
		private readonly Explorer $database,
		private readonly TexyFormatter $texyFormatter,
		private readonly InterviewMediaResources $interviewMediaResources,
	) {
	}


	/**
	 * @param int|null $limit
	 * @return Row[]
	 */
	public function getAll(?int $limit = null): array
	{
		$query = 'SELECT
				id_interview AS interviewId,
				action,
				title,
				date,
				href,
				video_href AS videoHref,
				audio_href AS audioHref,
				source_name AS sourceName,
				source_href AS sourceHref
			FROM interviews
			ORDER BY date DESC
			LIMIT ?';

		$result = $this->database->fetchAll($query, $limit ?? PHP_INT_MAX);
		foreach ($result as $row) {
			$this->enrich($row);
		}

		return $result;
	}


	/**
	 * @param string $name
	 * @return Row<mixed>
	 * @throws InterviewDoesNotExistException
	 */
	public function get(string $name): Row
	{
		/** @var Row<mixed>|null $result */
		$result = $this->database->fetch(
			'SELECT
				id_interview AS interviewId,
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

		if (!$result) {
			throw new InterviewDoesNotExistException(name: $name);
		}

		$this->enrich($result);
		return $result;
	}


	/**
	 * @param int $id
	 * @return Row<mixed>
	 * @throws InterviewDoesNotExistException
	 */
	public function getById(int $id): Row
	{
		/** @var Row<mixed>|null $result */
		$result = $this->database->fetch(
			'SELECT
				id_interview AS interviewId,
				action,
				title,
				description,
				description AS descriptionTexy,
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

		if (!$result) {
			throw new InterviewDoesNotExistException(id: $id);
		}

		$this->enrich($result);
		return $result;
	}


	/**
	 * @param Row<mixed> $row
	 */
	private function enrich(Row $row): void
	{
		foreach (['description'] as $item) {
			if (isset($row[$item])) {
				if (!is_string($row[$item])) {
					throw new ShouldNotHappenException(sprintf("Item '%s' is a %s not a string", $item, get_debug_type($row[$item])));
				}
				$row[$item] = $this->texyFormatter->formatBlock($row[$item]);
			}
		}
		$row->videoThumbnailUrl = isset($row->videoThumbnail) ? $this->interviewMediaResources->getImageUrl($row->interviewId, $row->videoThumbnail) : null;
		$row->videoThumbnailAlternativeUrl = isset($row->videoThumbnailAlternative) ? $this->interviewMediaResources->getImageUrl($row->interviewId, $row->videoThumbnailAlternative) : null;
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
				'description' => (empty($description) ? null : $description),
				'date' => new DateTime($date),
				'href' => $href,
				'audio_href' => (empty($audioHref) ? null : $audioHref),
				'audio_embed' => (empty($audioEmbed) ? null : $audioEmbed),
				'video_href' => (empty($videoHref) ? null : $videoHref),
				'video_thumbnail' => $videoThumbnail,
				'video_thumbnail_alternative' => $videoThumbnailAlternative,
				'video_embed' => (empty($videoEmbed) ? null : $videoEmbed),
				'source_name' => (empty($sourceName) ? null : $sourceName),
				'source_href' => (empty($sourceHref) ? null : $sourceHref),
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
				'description' => (empty($description) ? null : $description),
				'date' => new DateTime($date),
				'href' => $href,
				'audio_href' => (empty($audioHref) ? null : $audioHref),
				'audio_embed' => (empty($audioEmbed) ? null : $audioEmbed),
				'video_href' => (empty($videoHref) ? null : $videoHref),
				'video_thumbnail' => $videoThumbnail,
				'video_thumbnail_alternative' => $videoThumbnailAlternative,
				'video_embed' => (empty($videoEmbed) ? null : $videoEmbed),
				'source_name' => (empty($sourceName) ? null : $sourceName),
				'source_href' => (empty($sourceHref) ? null : $sourceHref),
			],
		);
		return (int)$this->database->getInsertId();
	}

}
