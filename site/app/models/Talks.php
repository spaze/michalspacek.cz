<?php
namespace MichalSpacekCz;

/**
 * Talks model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Talks extends BaseModel
{


	public function getAll($limit = null)
	{
		$query = 'SELECT
				action,
				title,
				date,
				href,
				slides_href AS slidesHref,
				video_href AS videoHref,
				event,
				event_href AS eventHref
			FROM talks
			WHERE date <= NOW()
			ORDER BY date DESC';

		if ($limit !== null) {
			$this->database->getSupplementalDriver()->applyLimit($query, $limit, null);
		}

		return $this->database->fetchAll($query);
	}


	public function getUpcoming()
	{
		$query = 'SELECT
				action,
				title,
				date,
				href,
				slides_href AS slidesHref,
				video_href AS videoHref,
				event,
				event_href AS eventHref
			FROM talks
			WHERE date > NOW()
			ORDER BY date';

		return $this->database->fetchAll($query);
	}


	public function get($name)
	{
		$result = $this->database->fetch(
			'SELECT
				action,
				title,
				description,
				date,
				href,
				slides_href AS slidesHref,
				slides_embed AS slidesEmbed,
				video_href AS videoHref,
				video_embed AS videoEmbed,
				event,
				event_href AS eventHref
			FROM talks
			WHERE action = ?',
			$name
		);

		if ($result) {
			$result['description'] = $this->texyFormatter->format($result['description']);
		}

		return $result;
	}


}