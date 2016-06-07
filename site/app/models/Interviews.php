<?php
namespace MichalSpacekCz;

/**
 * Interviews model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Interviews
{

	/** @var \Nette\Database\Context */
	protected $database;

	/** @var \Netxten\Formatter\Texy */
	protected $texyFormatter;


	public function __construct(\Nette\Database\Context $context, \Netxten\Formatter\Texy $texyFormatter)
	{
		$this->database = $context;
		$this->texyFormatter = $texyFormatter;
	}


	public function getAll($limit = null)
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


	public function get($name)
	{
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


	public function getById($id)
	{
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


	private function format(\Nette\Database\Row $row)
	{
		foreach (['description'] as $item) {
			if (isset($row[$item])) {
				$row[$item] = $this->texyFormatter->formatBlock($row[$item]);
			}
		}
	}


	/**
	 * Update interview data.
	 *
	 * @param integer $id
	 * @param string $action
	 * @param string $title
	 * @param string $description
	 * @param string $date
	 * @param string $href
	 * @param string $audioHref
	 * @param string $audioEmbed
	 * @param string $videoHref
	 * @param string $videoEmbed
	 * @param string $sourceName
	 * @param string $sourceHref
	 */
	public function update($id, $action, $title, $description, $date, $href, $audioHref, $audioEmbed, $videoHref, $videoEmbed, $sourceName, $sourceHref)
	{
		$this->database->query(
			'UPDATE interviews SET ? WHERE id_interview = ?',
			array(
				'action' => $action,
				'title' => $title,
				'description' => (empty($description) ? null : $description),
				'date' => new \DateTime($date),
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


	/**
	 * Insert interview data.
	 *
	 * @param string $action
	 * @param string $title
	 * @param string $description
	 * @param string $date
	 * @param string $href
	 * @param string $audioHref
	 * @param string $audioEmbed
	 * @param string $videoHref
	 * @param string $videoEmbed
	 * @param string $sourceName
	 * @param string $sourceHref
	 */
	public function add($action, $title, $description, $date, $href, $audioHref, $audioEmbed, $videoHref, $videoEmbed, $sourceName, $sourceHref)
	{
		$this->database->query(
			'INSERT INTO interviews',
			array(
				'action' => $action,
				'title' => $title,
				'description' => (empty($description) ? null : $description),
				'date' => new \DateTime($date),
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
