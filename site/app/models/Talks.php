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

	/** @var \Bare\Formatter\Texy */
	protected $texyFormatter;


	public function __construct(\Nette\Database\Context $context, \Bare\Next\Formatter\Texy $texyFormatter)
	{
		$this->database = $context;
		$this->texyFormatter = $texyFormatter;
	}


	public function getAll($limit = null)
	{
		$query = 'SELECT
				t.action,
				t.title,
				t.date,
				t.href,
				COALESCE(t.slides_href, t2.slides_href) AS slidesHref,
				t.video_href AS videoHref,
				t.event,
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
				t.action,
				t.title,
				t.date,
				t.href,
				COALESCE(t.slides_href, t2.slides_href) AS slidesHref,
				t.video_href AS videoHref,
				t.event,
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
				t.action,
				t.title,
				t.description,
				t.date,
				t.href,
				COALESCE(t.slides_href, t2.slides_href) AS slidesHref,
				COALESCE(t.slides_embed, t2.slides_embed) AS slidesEmbed,
				t.video_href AS videoHref,
				t.video_embed AS videoEmbed,
				t.event,
				t.event_href AS eventHref,
				COALESCE(t.og_image, t2.og_image) AS ogImage,
				COALESCE(t.transcript, t2.transcript) AS transcript
			FROM talks t
				LEFT JOIN talks t2 ON t.key_talk_slides = t2.id_talk
			WHERE t.action = ?',
			$name
		);

		if ($result) {
			$this->format($result);
		}

		return $result;
	}


	private function format(\Nette\Database\Row $row)
	{
		$format = array('title', 'description', 'event', 'transcript');
		foreach ($format as $item) {
			if (isset($row[$item])) {
				$row[$item] = $this->texyFormatter->format($row[$item]);
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

}
