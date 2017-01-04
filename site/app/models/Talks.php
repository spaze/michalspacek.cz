<?php
namespace MichalSpacekCz;

/**
 * Talks model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Talks
{

	/** @var \Nette\Database\Context */
	protected $database;

	/** @var \MichalSpacekCz\Formatter\Texy */
	protected $texyFormatter;


	public function __construct(\Nette\Database\Context $context, \Netxten\Formatter\Texy $texyFormatter)
	{
		$this->database = $context;
		$this->texyFormatter = $texyFormatter;
	}


	public function getAll($limit = null)
	{
		$query = 'SELECT
				t.id_talk AS talkId,
				t.action,
				t.title,
				t.title AS titleTexy,
				t.date,
				t.duration,
				t.href,
				COALESCE(LENGTH(t.slides_href) > 0, LENGTH(t2.slides_href) > 0, 0) AS hasSlides,
				t.slides_href,
				t.video_href AS videoHref,
				t.event,
				t.event AS eventTexy,
				t.event_href AS eventHref
			FROM talks t
				LEFT JOIN talks t2 ON t.key_talk_slides = t2.id_talk
			WHERE t.date <= NOW()
			ORDER BY t.date DESC';

		if ($limit !== null) {
			$this->database->getConnection()->getSupplementalDriver()->applyLimit($query, $limit, null);
		}

		$result = $this->database->fetchAll($query);
		foreach ($result as $row) {
			$this->format($row);
		}

		return $result;
	}


	public function getUpcoming()
	{
		$query = 'SELECT
				t.id_talk AS talkId,
				t.action,
				t.title,
				t.title AS titleTexy,
				t.date,
				t.duration,
				t.href,
				COALESCE(LENGTH(t.slides_href) > 0, LENGTH(t2.slides_href) > 0, 0) AS hasSlides,
				t.slides_href,
				t.video_href AS videoHref,
				t.event,
				t.event AS eventTexy,
				t.event_href AS eventHref
			FROM talks t
				LEFT JOIN talks t2 ON t.key_talk_slides = t2.id_talk
			WHERE t.date > NOW()
			ORDER BY t.date';

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
				t.id_talk AS talkId,
				t.action,
				t.title,
				t.title AS titleTexy,
				t.description,
				t.description AS descriptionTexy,
				t.date,
				t.duration,
				t.href,
				t.slides_href AS slidesHref,
				t.slides_embed AS slidesEmbed,
				t.video_href AS videoHref,
				t.video_embed AS videoEmbed,
				t.event,
				t.event AS eventTexy,
				t.event_href AS eventHref,
				t.og_image AS ogImage,
				t.transcript,
				t.transcript AS transcriptTexy,
				t.favorite,
				t2.action AS origAction,
				t2.title AS origTitle,
				t3.action AS supersededByAction,
				t3.title AS supersededByTitle
			FROM talks t
				LEFT JOIN talks t2 ON t.key_talk_slides = t2.id_talk
				LEFT JOIN talks t3 ON t.key_superseded_by = t3.id_talk
			WHERE t.action = ?',
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
				t.id_talk AS talkId,
				t.action,
				t.title,
				t.title AS titleTexy,
				t.description,
				t.description AS descriptionTexy,
				t.date,
				t.duration,
				t.href,
				t.slides_href AS slidesHref,
				t.slides_embed AS slidesEmbed,
				t.video_href AS videoHref,
				t.video_embed AS videoEmbed,
				t.event,
				t.event AS eventTexy,
				t.event_href AS eventHref,
				t.og_image AS ogImage,
				t.transcript,
				t.transcript AS transcriptTexy,
				t.favorite,
				t2.action AS origAction,
				t2.title AS origTitle,
				t3.action AS supersededByAction,
				t3.title AS supersededByTitle
			FROM talks t
				LEFT JOIN talks t2 ON t.key_talk_slides = t2.id_talk
				LEFT JOIN talks t3 ON t.key_superseded_by = t3.id_talk
			WHERE t.id_talk = ?',
			$id
		);

		if ($result) {
			$this->format($result);
		}

		return $result;
	}


	private function format(\Nette\Database\Row $row)
	{
		foreach (['title', 'event'] as $item) {
			if (isset($row[$item])) {
				$row[$item] = $this->texyFormatter->format($row[$item]);
			}
		}
		foreach (['description', 'transcript'] as $item) {
			if (isset($row[$item])) {
				$row[$item] = $this->texyFormatter->formatBlock($row[$item]);
			}
		}
	}


	public function getFavorites()
	{
		$query = 'SELECT
				action,
				title,
				favorite
			FROM talks
			WHERE favorite IS NOT NULL
			ORDER BY date DESC';

		$result = array();
		foreach ($this->database->fetchAll($query) as $row) {
			$result[] = $this->texyFormatter->substitute($row['favorite'], [$row['title'], $row['action']]);
		}

		return $result;
	}


	/**
	 * Return slide number by given alias.
	 *
	 * @param integer $talkId
	 * @param string|null $alias
	 * @return integer|string|null Slide number or given alias if not found or null if no alias given
	 */
	public function getSlideNo($talkId, $alias)
	{
		if ($alias === null) {
			$slideNo = null;
		} else {
			$slideNo = $this->database->fetchField('SELECT number FROM talk_slides WHERE key_talk = ? AND alias = ?', $talkId, $alias);
			if (!$slideNo) {
				$slideNo = $alias;
			}
		}
		return $slideNo;
	}


	/**
	 * Update talk data.
	 *
	 * @param integer $id
	 * @param string $action
	 * @param string $title
	 * @param string $description
	 * @param string $date
	 * @param integer $duration
	 * @param string $href
	 * @param string $origSlides
	 * @param string $slidesHref
	 * @param string $slidesEmbed
	 * @param string $videoHref
	 * @param string $videoEmbed
	 * @param string $event
	 * @param string $eventHref
	 * @param string $ogImage
	 * @param string $transcript
	 * @param string $favorite
	 * @param string $supersededBy
	 */
	public function update($id, $action, $title, $description, $date, $duration, $href, $origSlides, $slidesHref, $slidesEmbed, $videoHref, $videoEmbed, $event, $eventHref, $ogImage, $transcript, $favorite, $supersededBy)
	{
		$this->database->query(
			'UPDATE talks SET ? WHERE id_talk = ?',
			array(
				'action' => (empty($action) ? null : $action),
				'title' => $title,
				'description' => (empty($description) ? null : $description),
				'date' => new \DateTime($date),
				'duration' => (empty($duration) ? null : $duration),
				'href' => (empty($href) ? null : $href),
				'key_talk_slides' => (empty($origSlides) ? null : $this->get($origSlides)->talkId),
				'slides_href' => (empty($slidesHref) ? null : $slidesHref),
				'slides_embed' => (empty($slidesEmbed) ? null : $slidesEmbed),
				'video_href' => (empty($videoHref) ? null : $videoHref),
				'video_embed' => (empty($videoEmbed) ? null : $videoEmbed),
				'event' => $event,
				'event_href' => (empty($eventHref) ? null : $eventHref),
				'og_image' => (empty($ogImage) ? null : $ogImage),
				'transcript' => (empty($transcript) ? null : $transcript),
				'favorite' => (empty($favorite) ? null : $favorite),
				'key_superseded_by' => (empty($supersededBy) ? null : $this->get($supersededBy)->talkId),
			),
			$id
		);
	}


	/**
	 * Insert talk data.
	 *
	 * @param string $action
	 * @param string $title
	 * @param string $description
	 * @param string $date
	 * @param integer $duration
	 * @param string $href
	 * @param string $origSlides
	 * @param string $slidesHref
	 * @param string $slidesEmbed
	 * @param string $videoHref
	 * @param string $videoEmbed
	 * @param string $event
	 * @param string $eventHref
	 * @param string $ogImage
	 * @param string $transcript
	 * @param string $favorite
	 * @param string $supersededBy
	 */
	public function add($action, $title, $description, $date, $duration, $href, $origSlides, $slidesHref, $slidesEmbed, $videoHref, $videoEmbed, $event, $eventHref, $ogImage, $transcript, $favorite, $supersededBy)
	{
		$this->database->query(
			'INSERT INTO talks',
			array(
				'action' => (empty($action) ? null : $action),
				'title' => $title,
				'description' => (empty($description) ? null : $description),
				'date' => new \DateTime($date),
				'duration' => (empty($duration) ? null : $duration),
				'href' => (empty($href) ? null : $href),
				'key_talk_slides' => (empty($origSlides) ? null : $this->get($origSlides)->talkId),
				'slides_href' => (empty($slidesHref) ? null : $slidesHref),
				'slides_embed' => (empty($slidesEmbed) ? null : $slidesEmbed),
				'video_href' => (empty($videoHref) ? null : $videoHref),
				'video_embed' => (empty($videoEmbed) ? null : $videoEmbed),
				'event' => $event,
				'event_href' => (empty($eventHref) ? null : $eventHref),
				'og_image' => (empty($ogImage) ? null : $ogImage),
				'transcript' => (empty($transcript) ? null : $transcript),
				'favorite' => (empty($favorite) ? null : $favorite),
				'key_superseded_by' => (empty($supersededBy) ? null : $this->get($supersededBy)->talkId),
			)
		);
	}


	/**
	 * Get slides for talk.
	 *
	 * @param integer $talkId Talk id
	 * @return array of \Nette\Database\Row
	 */
	public function getSlides($talkId)
	{
		$result = $this->database->fetchAll(
			'SELECT
				alias,
				number
			FROM talk_slides
			WHERE key_talk = ?
			ORDER BY number',
			$talkId
		);
		return $result;
	}


	/**
	 * Insert slide.
	 *
	 * @param integer $talkId
	 * @param string $alias
	 * @param string $number
	 * @throws \UnexpectedValueException on duplicate entry (key_talk, number)
	 * @throws \PDOException
	 */
	public function addSlide($talkId, $alias, $number)
	{
		try {
			$this->database->query(
				'INSERT INTO talk_slides',
				array(
					'key_talk' => $talkId,
					'alias' => $alias,
					'number' => $number,
				)
			);
		} catch (\PDOException $e) {
			if ($e->getCode() == '23000') {
				if ($e->errorInfo[1] == \Nette\Database\Drivers\MySqlDriver::ERROR_DUPLICATE_ENTRY) {
					throw new \UnexpectedValueException($e->getMessage(), $number);
				}
			}
			throw $e;
		}
	}

}
