<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Interviews;

use DateTime;
use Nette\Database\Context;
use Nette\Database\Row;
use Netxten\Formatter\Texy;

class Interviews
{

	/** @var Context */
	protected $database;

	/** @var Texy */
	protected $texyFormatter;


	public function __construct(Context $context, Texy $texyFormatter)
	{
		$this->database = $context;
		$this->texyFormatter = $texyFormatter;
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
			ORDER BY date DESC';

		if ($limit !== null) {
			$this->database->getConnection()->getSupplementalDriver()->applyLimit($query, $limit, null);
		}

		$result = $this->database->fetchAll($query);
		foreach ($result as $row) {
			$this->format($row);
		}

		return $result;
	}


	/**
	 * @param string $name
	 * @return Row<mixed>|null
	 */
	public function get(string $name): ?Row
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
				video_embed AS videoEmbed,
				source_name AS sourceName,
				source_href AS sourceHref
			FROM interviews
			WHERE action = ?',
			$name
		);

		if ($result) {
			$this->format($result);
		}

		return $result;
	}


	/**
	 * @param integer $id
	 * @return Row<mixed>|null
	 */
	public function getById(int $id): ?Row
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
				video_embed AS videoEmbed,
				source_name AS sourceName,
				source_href AS sourceHref
			FROM interviews
			WHERE id_interview = ?',
			$id
		);

		if ($result) {
			$this->format($result);
		}

		return $result;
	}


	/**
	 * @param Row<mixed> $row
	 */
	private function format(Row $row): void
	{
		foreach (['description'] as $item) {
			if (isset($row[$item])) {
				$row[$item] = $this->texyFormatter->formatBlock($row[$item]);
			}
		}
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
		string $videoEmbed,
		string $sourceName,
		string $sourceHref
	): void {
		$this->database->query(
			'UPDATE interviews SET ? WHERE id_interview = ?',
			array(
				'action' => $action,
				'title' => $title,
				'description' => (empty($description) ? null : $description),
				'date' => new DateTime($date),
				'href' => $href,
				'audio_href' => (empty($audioHref) ? null : $audioHref),
				'audio_embed' => (empty($audioEmbed) ? null : $audioEmbed),
				'video_href' => (empty($videoHref) ? null : $videoHref),
				'video_embed' => (empty($videoEmbed) ? null : $videoEmbed),
				'source_name' => (empty($sourceName) ? null : $sourceName),
				'source_href' => (empty($sourceHref) ? null : $sourceHref),
			),
			$id
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
		string $videoEmbed,
		string $sourceName,
		string $sourceHref
	): void {
		$this->database->query(
			'INSERT INTO interviews',
			array(
				'action' => $action,
				'title' => $title,
				'description' => (empty($description) ? null : $description),
				'date' => new DateTime($date),
				'href' => $href,
				'audio_href' => (empty($audioHref) ? null : $audioHref),
				'audio_embed' => (empty($audioEmbed) ? null : $audioEmbed),
				'video_href' => (empty($videoHref) ? null : $videoHref),
				'video_embed' => (empty($videoEmbed) ? null : $videoEmbed),
				'source_name' => (empty($sourceName) ? null : $sourceName),
				'source_href' => (empty($sourceHref) ? null : $sourceHref),
			)
		);
	}

}
